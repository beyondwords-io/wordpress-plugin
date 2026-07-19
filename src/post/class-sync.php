<?php
/**
 * WordPress ↔ BeyondWords post sync.
 *
 * Owns the post-save / trash / delete handlers and the meta key registration —
 * the bridge between WordPress post lifecycle events and the BeyondWords API.
 * Block-editor JS bootstrap lives in [src/editor/block/class-assets.php](src/editor/block/class-assets.php).
 *
 * @package BeyondWords\Post
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 *                Editor enqueue moved to BeyondWords\Editor\Editor.
 *                Renamed from BeyondWords\Core\Core to BeyondWords\Post\Sync.
 */

declare( strict_types = 1 );

namespace BeyondWords\Post;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress post → BeyondWords API sync.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Sync {

	/**
	 * Cron hook that runs deferred audio (re)generation.
	 *
	 * Only scheduled on WordPress VIP (see `is_async_generation_enabled()`).
	 *
	 * @since 7.0.0
	 */
	const GENERATE_AUDIO_CRON_HOOK = 'beyondwords_generate_audio';

	/**
	 * Cron hook that runs a deferred audio deletion.
	 *
	 * Like GENERATE_AUDIO_CRON_HOOK, only scheduled when async is enabled (VIP).
	 * The event args carry the BeyondWords project + content IDs — not a post ID —
	 * because the post meta is wiped (on trash) or the post row is gone (on
	 * permanent delete) by the time the job runs.
	 *
	 * @since 7.0.0
	 */
	const DELETE_AUDIO_CRON_HOOK = 'beyondwords_delete_audio';

	/**
	 * Default maximum number of posts the bulk "Generate audio" action processes
	 * synchronously in a single admin request when async generation is
	 * unavailable (i.e. off VIP).
	 *
	 * Off VIP each post is a blocking API call, so an unbounded loop over a large
	 * selection could run the request past the PHP/host execution limit. We cap
	 * the count and defer the remainder (the caller surfaces a notice). Tune with
	 * the `beyondwords_bulk_generate_sync_limit` filter.
	 *
	 * @since 7.0.0
	 */
	const BULK_GENERATE_SYNC_LIMIT = 10;

	/**
	 * Per-call API timeout (in seconds) used while the bulk "Generate audio"
	 * action runs synchronously off VIP.
	 *
	 * Lower than `\BeyondWords\Api\Client::REQUEST_TIMEOUT` so a slow or
	 * unresponsive API can't stack up to a request-killing total across the
	 * capped batch. Still generous enough for a create/update, which is why this
	 * sits above the short `Client::DELETE_TIMEOUT`.
	 *
	 * @since 7.0.0
	 */
	const BULK_GENERATE_TIMEOUT = 15;

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'init', [ self::class, 'register_meta' ], 99, 3 );

		add_action( 'wp_after_insert_post', [ self::class, 'on_add_or_update_post' ], 99 );
		add_action( 'wp_trash_post', [ self::class, 'on_trash_post' ] );
		add_action( 'before_delete_post', [ self::class, 'on_delete_post' ] );

		// Background audio (re)generation — only used when async is enabled (VIP);
		// the hook is always registered so a queued event still runs after a
		// config change.
		add_action( self::GENERATE_AUDIO_CRON_HOOK, [ self::class, 'generate_audio_for_post' ] );

		// Background audio deletion — the deferred counterpart to the trash/delete
		// handlers. Registered unconditionally (like the generation hook) so a
		// queued event still runs after a config change. Takes the project +
		// content IDs as args because the post meta is gone by the time it fires.
		add_action( self::DELETE_AUDIO_CRON_HOOK, [ self::class, 'delete_audio_by_ids' ], 10, 2 );

		add_filter( 'is_protected_meta', [ self::class, 'is_protected_meta' ], 10, 2 );

		// Older posts may be missing `beyondwords_language_code`; back-fill from
		// the legacy `beyondwords_language_id` mapping when read.
		add_filter( 'get_post_metadata', [ self::class, 'get_lang_code_from_json_if_empty' ], 10, 3 );
	}

	/**
	 * Whether a given post status is one BeyondWords processes audio for.
	 *
	 * Defaults: pending, publish, private, future. Filterable via
	 * `beyondwords_settings_post_statuses`.
	 */
	public static function should_process_post_status( string $status ): bool {
		$statuses = [ 'pending', 'publish', 'private', 'future' ];

		/**
		 * Filters the post statuses BeyondWords processes audio for.
		 *
		 * @since 3.3.3 Introduced as `beyondwords_post_statuses`.
		 * @since 3.7.0 Added `pending` to the defaults.
		 * @since 4.3.0 Renamed to `beyondwords_settings_post_statuses`.
		 *
		 * @param string[] $statuses Post statuses to process.
		 */
		$statuses = apply_filters( 'beyondwords_settings_post_statuses', $statuses );

		return is_array( $statuses ) && in_array( $status, $statuses, true );
	}

	/**
	 * Whether to (re)generate audio for a post on save.
	 *
	 * Returns false for autosaves, revisions, and ineligible statuses.
	 *
	 * An explicit `beyondwords_generate_audio` value always wins ('1' = yes,
	 * '0' = no). When it is unset we fall back to the Preselect setting, but
	 * only for editor/REST saves: the block editor derives the toggle from
	 * Preselect without writing meta (so the post isn't dirtied — improvement
	 * #2), so the generate decision has to come from the setting at save time.
	 * The classic editor writes an explicit value via its checkbox, and
	 * programmatic/imported posts (wp_insert_post, WXR import, cron) keep the
	 * explicit-meta requirement so a bulk import never unexpectedly generates.
	 */
	public static function should_generate_audio_for_post( int $post_id ): bool {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}

		// get_post_status() returns false when the post no longer exists — e.g. it
		// was trashed or permanently deleted between a deferred job being queued
		// (see on_add_or_update_post()) and the cron firing. An empty status is
		// never processable, so bail before the strict-typed
		// should_process_post_status() call, which would TypeError on false.
		$status = get_post_status( $post_id );
		if ( ! $status ) {
			return false;
		}

		if ( ! self::should_process_post_status( $status ) ) {
			return false;
		}

		// Read via get_renamed_post_meta so a legacy post that still only has
		// the old `speechkit_generate_audio` key is honoured (and migrated to
		// `beyondwords_generate_audio` on read).
		$generate_audio = \BeyondWords\Post\Meta::get_renamed_post_meta( $post_id, 'generate_audio' );

		if ( '1' === $generate_audio ) {
			return true;
		}

		if ( '0' === $generate_audio ) {
			return false;
		}

		// Unset: honour Preselect, but only on an editor/REST save.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return \BeyondWords\Settings\Preselect::should_preselect_for_post( $post_id );
		}

		return false;
	}

	/**
	 * Whether audio (re)generation should run in the background rather than
	 * blocking the save request.
	 *
	 * Enabled only on WordPress VIP, whose Cron Control infrastructure runs
	 * scheduled events from a real system cron — so a deferred job fires
	 * promptly and reliably. Off-VIP, WP-Cron is traffic-triggered and
	 * unreliable, so we keep the synchronous call and accept the slower save.
	 *
	 * Detection is by VIP-only symbols (`function_exists`/`class_exists`/
	 * `defined`), so non-VIP installs always take the synchronous path.
	 * Overridable via the `beyondwords_async_generate_audio` filter for hosts
	 * with reliable cron, and for tests.
	 *
	 * @since 7.0.0
	 */
	public static function is_async_generation_enabled(): bool {
		// VIP runs WP-Cron via its Cron Control plugin; these symbols only exist
		// in that environment.
		$enabled = class_exists( '\Automattic\WP\Cron_Control\Main' )
			|| function_exists( 'wpcom_vip_schedule_single_event' )
			|| defined( 'VIP_GO_APP_ENVIRONMENT' );

		/**
		 * Filters whether BeyondWords audio (re)generation runs in the background.
		 *
		 * Defaults to true only on WordPress VIP. Return true to force background
		 * generation on another host with reliable WP-Cron.
		 *
		 * @since 7.0.0
		 *
		 * @param bool $enabled Whether background (cron) generation is enabled.
		 */
		return (bool) apply_filters( 'beyondwords_async_generate_audio', $enabled );
	}

	/**
	 * Generate audio for a post if eligibility checks pass.
	 *
	 * For Magic Embed (client-side) we POST to the by-source-id endpoint and
	 * stamp the post meta. For REST API integration we either update existing
	 * audio (with 404 recovery via `update_or_recreate_audio()`) or create
	 * fresh content.
	 *
	 * @param int $post_id WordPress post ID.
	 * @param int $timeout Per-call API timeout in seconds. Defaults to
	 *                     `Client::REQUEST_TIMEOUT`; the capped synchronous bulk
	 *                     path passes the shorter `BULK_GENERATE_TIMEOUT`.
	 *
	 * @return array<mixed>|false|null Response from the API, or false when audio wasn't generated.
	 */
	public static function generate_audio_for_post( int $post_id, int $timeout = \BeyondWords\Api\Client::REQUEST_TIMEOUT ): array|false|null {
		if ( ! self::should_generate_audio_for_post( $post_id ) ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$integration_method = \BeyondWords\Settings\Fields::get_integration_method( $post );

		// Magic Embed: import via the source ID endpoint.
		if ( \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE === $integration_method ) {
			update_post_meta( $post_id, 'beyondwords_integration_method', \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE );
			update_post_meta( $post_id, 'beyondwords_project_id', get_option( 'beyondwords_project_id' ) );

			return \BeyondWords\Api\Client::get_player_by_source_id( $post_id );
		}

		// REST API integration: update or create.
		update_post_meta( $post_id, 'beyondwords_integration_method', \BeyondWords\Settings\Fields::INTEGRATION_REST_API );

		$content_id = \BeyondWords\Post\Meta::get_content_id( $post_id );

		if ( $content_id ) {
			if ( defined( 'BEYONDWORDS_AUTOREGENERATE' ) && ! BEYONDWORDS_AUTOREGENERATE ) {
				return false;
			}

			$response = self::update_or_recreate_audio( $post_id, $timeout );
		} else {
			$response = \BeyondWords\Api\Client::create_audio( $post_id, $timeout );
		}

		$project_id = \BeyondWords\Post\Meta::get_project_id( $post_id );
		self::process_response( $response, $project_id, $post_id );

		return $response;
	}

	/**
	 * Update audio for a post, recovering from a stale content ID.
	 *
	 * When BeyondWords returns 404 (content no longer exists in their database),
	 * `\BeyondWords\Api\Client::update_audio()` writes `#404:…` into the error meta. We
	 * detect that prefix, clear the stale ID, and create fresh content via
	 * POST so the post recovers automatically.
	 *
	 * @param int $post_id WordPress post ID.
	 * @param int $timeout Per-call API timeout in seconds, forwarded to both the
	 *                     update and any recovery create.
	 */
	private static function update_or_recreate_audio( int $post_id, int $timeout = \BeyondWords\Api\Client::REQUEST_TIMEOUT ): array|null|false {
		$response = \BeyondWords\Api\Client::update_audio( $post_id, $timeout );

		$error_message = (string) get_post_meta( $post_id, 'beyondwords_error_message', true );

		if ( str_starts_with( $error_message, '#404:' ) ) {
			delete_post_meta( $post_id, 'beyondwords_content_id' );
			delete_post_meta( $post_id, 'beyondwords_podcast_id' );
			delete_post_meta( $post_id, 'speechkit_podcast_id' );

			$response = \BeyondWords\Api\Client::create_audio( $post_id, $timeout );
		}

		return $response;
	}

	/**
	 * Delete audio for a single post (DELETE /content/:id).
	 */
	public static function delete_audio_for_post( int $post_id ): array|false|null {
		return \BeyondWords\Api\Client::delete_audio( $post_id );
	}

	/**
	 * Bulk-delete audio for multiple posts.
	 *
	 * @param int[] $post_ids
	 */
	public static function batch_delete_audio_for_posts( array $post_ids ): array|false|null {
		return \BeyondWords\Api\Client::batch_delete_audio( $post_ids );
	}

	/**
	 * Dispatch bulk "Generate audio" for a set of posts without blocking the
	 * admin request on an unbounded run of remote API calls.
	 *
	 * Every selected post is flagged for generation (a cheap meta write). Then:
	 *
	 * - On VIP (async enabled) each post is queued as a background cron job, so
	 *   the request returns immediately and no API call runs inline. All selected
	 *   posts are reported as "generated" (i.e. requested).
	 * - Off VIP (async disabled) we can't rely on WP-Cron, so generation runs
	 *   inline — but capped at `bulk_generate_sync_limit()` posts and with a lower
	 *   per-call timeout, so a slow API can't run the request past the execution
	 *   limit. Posts still missing audio are processed before regenerations, and
	 *   any beyond the cap are returned as "deferred" for the caller to surface.
	 *
	 * @since 7.0.0
	 *
	 * @param int[] $post_ids WordPress post IDs from the bulk selection.
	 *
	 * @return array{generated:int, failed:int, deferred:int} Per-outcome counts.
	 */
	public static function bulk_generate_audio_for_posts( array $post_ids ): array {
		$post_ids = array_map( 'intval', $post_ids );
		$post_ids = array_values( array_unique( array_filter( $post_ids, static fn( int $id ): bool => $id > 0 ) ) );
		sort( $post_ids );

		// Flag every selected post for generation (cheap). On the async path this
		// is what the deferred cron job reads; off VIP it records intent for the
		// posts we can't process in this request.
		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, 'beyondwords_generate_audio', '1' );
		}

		// VIP: queue a background job per post and return immediately.
		if ( self::is_async_generation_enabled() ) {
			foreach ( $post_ids as $post_id ) {
				self::schedule_audio_generation( $post_id );
			}

			return [
				'generated' => count( $post_ids ),
				'failed'    => 0,
				'deferred'  => 0,
			];
		}

		// Off VIP: process synchronously, but hard-capped and with a lower
		// per-call timeout. Posts still missing audio are processed before
		// regenerations so re-running the action on a large selection converges.
		$ordered    = self::order_posts_for_bulk_generation( $post_ids );
		$limit      = self::bulk_generate_sync_limit();
		$to_process = array_slice( $ordered, 0, $limit );
		$deferred   = count( $ordered ) - count( $to_process );

		$generated = 0;
		$failed    = 0;

		foreach ( $to_process as $post_id ) {
			if ( self::generate_audio_for_post( $post_id, self::BULK_GENERATE_TIMEOUT ) ) {
				++$generated;
			} else {
				++$failed;
			}
		}

		return [
			'generated' => $generated,
			'failed'    => $failed,
			'deferred'  => $deferred,
		];
	}

	/**
	 * Order a bulk selection so posts that still need audio created are processed
	 * before posts that already have audio (a regeneration).
	 *
	 * Keeps the synchronous cap making forward progress: re-running the action on
	 * a large selection works through the un-generated posts rather than
	 * re-updating the same already-done ones each time.
	 *
	 * @param int[] $post_ids Normalised, sorted post IDs.
	 *
	 * @return int[]
	 */
	private static function order_posts_for_bulk_generation( array $post_ids ): array {
		$needs_create = [];
		$needs_update = [];

		foreach ( $post_ids as $post_id ) {
			if ( \BeyondWords\Post\Meta::get_content_id( $post_id ) ) {
				$needs_update[] = $post_id;
			} else {
				$needs_create[] = $post_id;
			}
		}

		return array_merge( $needs_create, $needs_update );
	}

	/**
	 * Resolve the synchronous bulk-generation cap, honouring the
	 * `beyondwords_bulk_generate_sync_limit` filter and guarding against a
	 * non-positive override.
	 *
	 * @since 7.0.0
	 *
	 * @return int
	 */
	private static function bulk_generate_sync_limit(): int {
		/**
		 * Filters the maximum number of posts the bulk "Generate audio" action
		 * processes synchronously per request when async generation is off (VIP
		 * is the async path). Non-positive values fall back to the default.
		 *
		 * @since 7.0.0
		 *
		 * @param int $limit Maximum posts to process synchronously. Default 10.
		 */
		$limit = (int) apply_filters( 'beyondwords_bulk_generate_sync_limit', self::BULK_GENERATE_SYNC_LIMIT );

		return $limit > 0 ? $limit : self::BULK_GENERATE_SYNC_LIMIT;
	}

	/**
	 * Run a deferred audio deletion queued by the trash/delete handlers.
	 *
	 * The `DELETE_AUDIO_CRON_HOOK` cron event carries the BeyondWords project +
	 * content IDs directly (not a post ID) because the post meta has already been
	 * wiped — or the post row deleted — by the time this fires. Only reached on
	 * the async path, which is VIP-only by default (see
	 * `is_async_generation_enabled()`).
	 *
	 * @since 7.0.0
	 *
	 * @param int|string $project_id BeyondWords project ID.
	 * @param int|string $content_id BeyondWords content ID.
	 */
	public static function delete_audio_by_ids( int|string $project_id, int|string $content_id ): array|false|null {
		return \BeyondWords\Api\Client::delete_audio_by_ids( $project_id, $content_id );
	}

	/**
	 * Persist relevant fields from a BeyondWords API response into post meta.
	 *
	 * @param mixed            $response   API response (typically an associative array).
	 * @param int|string|false $project_id BeyondWords project ID.
	 * @param int              $post_id    WordPress post ID.
	 *
	 * @return mixed The response, unchanged.
	 */
	public static function process_response( mixed $response, int|string|false $project_id, int $post_id ): mixed {
		if ( ! is_array( $response ) ) {
			return $response;
		}

		if ( $project_id && ! empty( $response['id'] ) ) {
			update_post_meta( $post_id, 'beyondwords_project_id', $project_id );
			update_post_meta( $post_id, 'beyondwords_content_id', $response['id'] );

			// We deliberately do NOT copy `language` or `body_voice_id` back from
			// the API response. Those meta keys hold the editor's *explicit*
			// language/voice choices ("Customize" on); echoing the project-default
			// values the API resolved would make a default post look customised
			// and freeze its voice so it no longer follows the project default.
			// See Content::get_content_params().
			$copy = [
				'preview_token' => 'beyondwords_preview_token',
			];

			foreach ( $copy as $api_key => $meta_key ) {
				if ( ! empty( $response[ $api_key ] ) ) {
					update_post_meta( $post_id, $meta_key, $response[ $api_key ] );
				}
			}
		}

		return $response;
	}

	/**
	 * Register every BeyondWords post-meta key for REST + auth gating.
	 *
	 * Registers per-(post_type, meta_key) combination so the meta is exposed
	 * only on compatible post types.
	 */
	public static function register_meta(): void {
		$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

		if ( ! is_array( $post_types ) ) {
			return;
		}

		$keys = \BeyondWords\Core\Utils::get_post_meta_keys( 'all' );

		foreach ( $post_types as $post_type ) {
			$options = [
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'default'           => '',
				'object_subtype'    => $post_type,
				'prepare_callback'  => 'sanitize_text_field',
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
			];

			foreach ( $keys as $key ) {
				register_meta( 'post', $key, $options );
			}
		}
	}

	/**
	 * Hide BeyondWords meta from the legacy "Custom Fields" panel.
	 *
	 * The Block Editor's Custom Fields panel can break when our meta is
	 * shown alongside the auto-rendered controls — see
	 * https://github.com/WordPress/gutenberg/issues/23078.
	 *
	 * @param bool|null   $is_protected Whether the meta is currently flagged protected.
	 * @param string|null $meta_key     Meta key being checked. Null is passed by some core paths.
	 */
	public static function is_protected_meta( $is_protected, $meta_key ): bool {
		if ( null === $meta_key ) {
			return (bool) $is_protected;
		}

		if ( in_array( $meta_key, \BeyondWords\Core\Utils::get_post_meta_keys( 'all' ), true ) ) {
			return true;
		}

		return (bool) $is_protected;
	}

	/**
	 * Schedule a deferred audio-generation cron event for a post.
	 *
	 * Only reached on the async path, which is VIP-only by default (see
	 * `is_async_generation_enabled()`). Prefers VIP's
	 * `wpcom_vip_schedule_single_event()` wrapper when it exists (classic
	 * WordPress.com VIP); on VIP Go — and anywhere the async filter is forced
	 * on — the core function is the correct call and is routed through Cron
	 * Control. No-ops when an event for this post is already queued, so repeated
	 * saves don't stack duplicate jobs.
	 *
	 * @since 7.0.0
	 */
	private static function schedule_audio_generation( int $post_id ): void {
		if ( wp_next_scheduled( self::GENERATE_AUDIO_CRON_HOOK, [ $post_id ] ) ) {
			return;
		}

		if ( function_exists( 'wpcom_vip_schedule_single_event' ) ) {
			wpcom_vip_schedule_single_event( time(), self::GENERATE_AUDIO_CRON_HOOK, [ $post_id ] );
			return;
		}

		wp_schedule_single_event( time(), self::GENERATE_AUDIO_CRON_HOOK, [ $post_id ] );
	}

	/**
	 * Schedule a deferred audio-deletion cron event.
	 *
	 * Mirrors `schedule_audio_generation()`: VIP-only in practice, prefers the
	 * VIP wrapper when present, and no-ops when an identical event is already
	 * queued so repeated trashing can't stack duplicate jobs. The project +
	 * content IDs are the event args (rather than a post ID) because the meta is
	 * gone by the time the job runs.
	 *
	 * @since 7.0.0
	 */
	private static function schedule_audio_deletion( int|string $project_id, int|string $content_id ): void {
		$args = [ $project_id, $content_id ];

		if ( wp_next_scheduled( self::DELETE_AUDIO_CRON_HOOK, $args ) ) {
			return;
		}

		if ( function_exists( 'wpcom_vip_schedule_single_event' ) ) {
			wpcom_vip_schedule_single_event( time(), self::DELETE_AUDIO_CRON_HOOK, $args );
			return;
		}

		wp_schedule_single_event( time(), self::DELETE_AUDIO_CRON_HOOK, $args );
	}

	/**
	 * Clear any pending deferred audio-generation cron event for a post.
	 *
	 * Called when a post is trashed or deleted so a job queued by
	 * `on_add_or_update_post()` doesn't fire for a post that no longer exists.
	 * Runs before the `has_content()` checks in the lifecycle handlers because a
	 * queued post may not have written its content meta yet. `wp_clear_scheduled_hook()`
	 * is routed through Cron Control on VIP, so it unschedules reliably there too.
	 *
	 * @since 7.0.0
	 */
	private static function unschedule_audio_generation( int $post_id ): void {
		wp_clear_scheduled_hook( self::GENERATE_AUDIO_CRON_HOOK, [ $post_id ] );
	}

	/**
	 * Delete a post's BeyondWords audio, deferring to background cron on VIP.
	 *
	 * On VIP (async enabled) we capture the project + content IDs now and
	 * schedule a single background event, so the trash/delete request — which may
	 * be removing many posts at once — returns immediately instead of blocking on
	 * one remote DELETE per post. We read the IDs before the caller wipes the meta
	 * (on trash) or WordPress deletes the row (on permanent delete). Off-VIP,
	 * where WP-Cron is unreliable, we fall back to a synchronous DELETE, bounded
	 * by the client's short `DELETE_TIMEOUT`.
	 *
	 * The caller must have confirmed `Meta::has_content()` first.
	 *
	 * @since 7.0.0
	 */
	private static function delete_audio_for_post_or_defer( int $post_id ): void {
		if ( self::is_async_generation_enabled() ) {
			$project_id = \BeyondWords\Post\Meta::get_project_id( $post_id );
			$content_id = \BeyondWords\Post\Meta::get_content_id( $post_id, true );

			if ( $project_id && $content_id ) {
				self::schedule_audio_deletion( $project_id, $content_id );
			}

			return;
		}

		self::delete_audio_for_post( $post_id );
	}

	/**
	 * Trash hook — DELETE the audio so it disappears from the dashboard, then
	 * remove our local metadata.
	 */
	public static function on_trash_post( $post_id ): void {
		$post_id = (int) $post_id;

		self::unschedule_audio_generation( $post_id );

		if ( ! \BeyondWords\Post\Meta::has_content( $post_id ) ) {
			return;
		}

		self::delete_audio_for_post_or_defer( $post_id );
		\BeyondWords\Post\Meta::remove_all_beyondwords_metadata( $post_id );
	}

	/**
	 * Permanent delete hook — same DELETE call as trash, without the meta cleanup
	 * (the row is going away anyway).
	 */
	public static function on_delete_post( $post_id ): void {
		$post_id = (int) $post_id;

		self::unschedule_audio_generation( $post_id );

		if ( ! \BeyondWords\Post\Meta::has_content( $post_id ) ) {
			return;
		}

		self::delete_audio_for_post_or_defer( $post_id );
	}

	/**
	 * `wp_after_insert_post` hook.
	 *
	 * Skips the second invocation that Gutenberg makes via the meta-box save
	 * round-trip, otherwise we'd double-process every save. When the post has
	 * `beyondwords_delete_content` set we delete instead of generating.
	 */
	public static function on_add_or_update_post( $post_id ): bool {
		$post_id = (int) $post_id;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['meta-box-loader'] ) && '' !== sanitize_key( wp_unslash( $_REQUEST['meta-box-loader'] ) ) ) {
			return false;
		}

		if ( '1' === get_post_meta( $post_id, 'beyondwords_delete_content', true ) ) {
			self::delete_audio_for_post( $post_id );
			\BeyondWords\Post\Meta::remove_all_beyondwords_metadata( $post_id );
			return false;
		}

		// On VIP, defer the blocking create/update API call to a background cron
		// job so the save request returns immediately. Off-VIP we fall through to
		// the synchronous call below, because WP-Cron there is traffic-triggered
		// and unreliable. The eligibility check is repeated inside
		// generate_audio_for_post() when the deferred job runs.
		if ( self::is_async_generation_enabled() && self::should_generate_audio_for_post( $post_id ) ) {
			self::schedule_audio_generation( $post_id );

			return true;
		}

		return (bool) self::generate_audio_for_post( $post_id );
	}

	/**
	 * Back-fill `beyondwords_language_code` from the legacy numeric language ID
	 * when the modern ISO-code meta is empty.
	 *
	 * Reads `assets/lang-codes.json` lazily — only when a stale post is queried.
	 *
	 * @param mixed       $value     Existing meta value.
	 * @param int         $object_id Post ID.
	 * @param string|null $meta_key  Meta key being read.
	 *
	 * @return mixed
	 */
	public static function get_lang_code_from_json_if_empty( $value, $object_id, $meta_key ): mixed {
		if ( 'beyondwords_language_code' !== $meta_key || ! empty( $value ) ) {
			return $value;
		}

		$language_id = get_post_meta( $object_id, 'beyondwords_language_id', true );

		if ( ! $language_id ) {
			return $value;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$lang_codes = json_decode( file_get_contents( BEYONDWORDS__PLUGIN_DIR . 'assets/lang-codes.json' ), true );

		if ( is_array( $lang_codes ) && array_key_exists( $language_id, $lang_codes ) ) {
			return [ $lang_codes[ $language_id ] ];
		}

		return $value;
	}
}
