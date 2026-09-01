# Duplicate `source_id` on create

Why creating content is guarded twice: a lock in
[src/post/class-sync.php](../src/post/class-sync.php) (`create_audio_once()`,
`acquire_create_lock()`) and a recovery in
[src/api/class-client.php](../src/api/class-client.php)
(`adopt_existing_content()`).

## The race

`wp_after_insert_post` can fire for one post in two overlapping requests — an
autosave landing as Publish is clicked is enough. Both read an empty
`beyondwords_content_id`, so both take the create branch and POST.

The API accepts the first and rejects the second:

```json
{"code":422,"message":"Invalid request body","errors":[{"location":"source_id","message":"has already been taken"}]}
```

Unhandled, the loser stores `#422: source_id has already been taken` in
`beyondwords_error_message`, leaves `beyondwords_content_id` empty and never
retries. The post then renders no player at all — auto-prepend and shortcode
both suppress on an empty content ID — even though the audio exists and
processed fine. Observed at roughly 1 publish in 18.

## Guard 1: don't make the second POST

`Sync::create_audio_once()` takes a per-post lock (`_beyondwords_create_lock`,
`Sync::CREATE_LOCK_TIMEOUT`) before creating, and re-reads the content ID under
it in case the winner finished in the meantime.

- The lock is taken with `add_post_meta( …, $unique: true )`, whose existence
  check is an **uncached** query, so two racing requests contend over a single
  statement rather than the whole API round trip.
- Both reads bust the post's meta cache first: a cache primed earlier in the
  request predates the other request's write.
- The response is stored **inside** the lock. Releasing first would leave a
  window where the lock is gone and the content ID still unwritten, which is
  exactly the state the lock exists to hide.
- A lock older than the timeout is stolen, so a request that dies mid-create
  can't lock a post out of audio permanently.

Limits, both absorbed by guard 2: the acquire is near-atomic, not atomic, and on
a host that routes reads to a replica neither request may see the other's row.
The trade-off is deliberate — if the lock holder's create fails (a timeout, say)
a genuine concurrent save is suppressed and the post waits for the next save.
Failures are usually correlated across concurrent requests, so this is narrower
than the double-create it prevents.

## Guard 2: adopt what the winner created

The content endpoint resolves **either** a content ID or a `source_id`, and the
plugin sends the post ID as the source ID (`Content::get_content_params()`). So
when a create fails in either of these ways, `Client::create_audio()` re-fetches
by source ID and adopts the record if it can be confirmed as this post's:

1. **422 duplicate `source_id`** — another request already created the content
   (the race above). Matched on the error's `location`, never the message text.
2. **Transport failure (`WP_Error`)** — the client gave up waiting after the API
   may already have accepted the POST. Creates get the longer
   `CONTENT_REQUEST_TIMEOUT` (filterable via
   `beyondwords_content_request_timeout`) precisely because they were observed
   outliving the default timeout, so this is the residual case. The probe is
   skipped when the request provably never left (`http_request_not_executed`),
   runs on the short `ADOPTION_PROBE_TIMEOUT`, and a probe that itself fails at
   transport level negative-caches for `CACHE_TTL_ON_ERROR` so an unreachable
   API isn't probed on every save.

`Sync::process_response()` then stores the content ID and preview token exactly
as after a successful create, and the error is cleared. A failed lookup leaves
the original error in place — the 422 on path 1, the `#500` (with the transport
detail appended) on path 2.

## Only adopt this post's content

Source IDs are bare post IDs and are **not** namespaced by site, so any second
install pointed at the same project — a staging clone, another subsite —
collides on every post ID. Adoption therefore demands two proofs from the
fetched record:

1. **`source_id` equals the post ID.** The lookup also resolves plain content
   IDs, and installs upgraded from older plugin versions still carry legacy
   *numeric* content IDs — so a bare post ID could otherwise resolve a
   different post's record that merely shares the number.
2. **`source_url` belongs to this install** — same host, and the record's path
   sits under this site's path at a segment boundary. Scheme and port are
   ignored so a site that moved from `http` to `https` still owns its pre-move
   content. The check is against the site root rather than the post's permalink
   because the two racing saves can straddle the draft → published permalink
   change.

Known limit: a *root-path* install cannot be told apart from a subdirectory
install on the same host by URL alone (every path sits under `/`), so two such
installs sharing one project can still cross-adopt when their post IDs collide.
Separate BeyondWords projects per install is the supported setup.

Content that can't be confirmed as this post's leaves the original error
stored, which is the pre-fix behaviour.
