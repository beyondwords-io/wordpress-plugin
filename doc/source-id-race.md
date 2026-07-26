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
a create rejected for a duplicate `source_id` can re-fetch the content that
already exists and adopt it: `Sync::process_response()` then stores the content
ID and preview token exactly as it would after a successful create, and the
error is cleared. The next save updates that content as normal.

Matched on the error's `location`, never the message text. Any other `source_id`
validation failure simply 404s the follow-up lookup, leaving the original error
in place.

## Only adopt this site's content

Source IDs are bare post IDs and are **not** namespaced by site, so any second
install pointed at the same project — a staging clone, another subsite —
collides on every post ID. Before the fix the 422 was what stopped those two
installs fighting over one content record.

So adoption requires the fetched record's `source_url` to sit under this site's
`home_url()`. Adopting blind would attach another site's content to this post
and overwrite it on the next update. The check is against the site root rather
than the post's permalink because the two racing saves can straddle the
draft → published permalink change.

Schemes are ignored in that comparison, so a site that has moved from `http` to
`https` still recognises content it created before the move. Host and path still
have to match, which is what separates a staging clone or a sibling subsite —
`example.com/site-a` doesn't match `example.com/site-b`.

Content that can't be confirmed as this site's leaves the 422 stored, which is
the pre-fix behaviour.
