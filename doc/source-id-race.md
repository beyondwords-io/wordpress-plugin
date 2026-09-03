# Duplicate `source_id` on create

Content creation is guarded twice: a per-post lock in
[src/post/class-sync.php](../src/post/class-sync.php) (`create_audio_once()`)
and a recovery in [src/api/class-client.php](../src/api/class-client.php)
(`adopt_existing_content()`).

## The race

`wp_after_insert_post` can fire twice for one post — an autosave landing as
Publish is clicked. Both requests see an empty `beyondwords_content_id` and
POST a create; the API 422s the loser with `errors[].location = "source_id"`.
Unhandled, the loser stored that 422 and the post never got a content ID
(observed at ~1 publish in 18).

## Guard 1: the create lock

`create_audio_once()` takes `_beyondwords_create_lock` with
`add_post_meta( …, $unique: true )` — an uncached existence check, so the
contention window is one statement — re-reads the content ID under the lock,
and stores the response before releasing. A lock older than
`CREATE_LOCK_TIMEOUT` is stolen. Near-atomic, not atomic: guard 2 absorbs the
misses, and a holder whose create fails suppresses a concurrent save on
purpose — failures correlate across concurrent requests, and the next save
retries.

## Guard 2: adoption

The content endpoint resolves a `source_id` as well as a content ID, and the
plugin uses the post ID as the source ID. After a failed create — a 422
duplicate `source_id` (matched on `location`, never message text), or a
transport `WP_Error` when the API may have accepted the POST anyway (creates
run at the filterable `CONTENT_REQUEST_TIMEOUT`; the follow-up probe uses
`ADOPTION_PROBE_TIMEOUT`, skips requests that provably never left WordPress,
and negative-caches an unreachable API) — `create_audio()` re-fetches by
source ID and adopts the record only when:

1. its `source_id` equals the post ID — the lookup also resolves legacy
   *numeric* content IDs, which can collide with post IDs; and
2. its `source_url` matches this install by host and path segment — scheme
   and port ignored, and checked against the site root rather than the
   permalink because the race can straddle the draft → published permalink
   change.

Adoption stores the content ID and preview token and clears the error; any
failed proof leaves the original error in place. Known limit: a root-path
install and a subdirectory install on the same host are indistinguishable by
URL, so two installs sharing one project can still cross-adopt — separate
BeyondWords projects per install is the supported setup.
