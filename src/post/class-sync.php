<?php
/**
 * WordPress ↔ BeyondWords post sync: save/trash/delete handlers and meta registration.
 *
 * @package BeyondWords\Post
 * @since   3.0.0
 * @since   7.0.0 Renamed from BeyondWords\Core\Core to BeyondWords\Post\Sync.
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
	 * Cron hook that runs deferred audio (re)generation (VIP-only; see is_async_generation_enabled()).
	 *
	 * @since 7.0.0
	 */
	const GENERATE_AUDIO_CRON_HOOK = 'beyondwords_generate_audio';

	/**
	 * Cron hook that runs a deferred audio deletion (VIP-only).
	 *
	 * Args are the BeyondWords project + content IDs, not a post ID — the post
	 * meta is wiped or the post row gone by the time the job runs.
	 *
	 * @since 7.0.0
	 */
	const DELETE_AUDIO_CRON_HOOK = 'beyondwords_delete_audio';

	/**
	 * Maximum posts the bulk "Generate audio" action processes synchronously (off VIP).
	 *
	 * Each post is a blocking API call, so the count is capped and the remainder
	 * deferred (the caller surfaces a notice) to stay inside execution limits.
	 *
	 * @since 7.0.0
	 */
	const BULK_GENERATE_SYNC_LIMIT = 10;

	/**
	 * Deprecated post-meta keys still exposed to the block editor over REST.
	 *
	 * On sites upgraded from legacy SpeechKit these can hold a post's only
	 * BeyondWords data, so the editor components read them as a fallback.
	 *
	 * @since 7.0.0
	 *
	 * @var string[]
	 */
	const REST_LEGACY_META_KEYS = [
		'beyondwords_podcast_id',
		'speechkit_generate_audio',
		'speechkit_project_id',
		'speechkit_podcast_id',
		'speechkit_error_message',
		'_speechkit_link',
	];

	/**
	 * REST-exposed post-meta keys that hold secrets or internal data.
	 *
	 * The hide_private_meta_from_rest() filter strips these from every
	 * non-`edit` response so unauthenticated requests never see them.
	 *
	 * @since 7.0.0
	 *
	 * @var string[]
	 */
	const REST_PRIVATE_META_KEYS = [
		'beyondwords_error_message',
		'beyondwords_preview_token',
		'speechkit_error_message',
		'_speechkit_link',
	];

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'init', [ self::class, 'register_meta' ], 99, 3 );
		add_action( 'rest_api_init', [ self::class, 'register_rest_meta_visibility' ] );

		add_action( 'wp_after_insert_post', [ self::class, 'on_add_or_update_post' ], 99 );
		add_action( 'wp_trash_post', [ self::class, 'on_trash_post' ] );
		add_action( 'before_delete_post', [ self::class, 'on_delete_post' ] );

		// Cron handlers are registered unconditionally so queued events still run
		// after a config change.
		add_action( self::GENERATE_AUDIO_CRON_HOOK, [ self::class, 'generate_audio_for_post' ] );
		add_action( self::DELETE_AUDIO_CRON_HOOK, [ self::class, 'delete_audio_by_ids' ], 10, 2 );

		add_filter( 'is_protected_meta', [ self::class, 'is_protected_meta' ], 10, 2 );
		add_filter( 'get_post_metadata', [ self::class, 'get_lang_code_from_json_if_empty' ], 10, 3 );
	}

	/**
	 * Whether a given post status is one BeyondWords processes audio for.
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
	 * An explicit `beyondwords_generate_audio` meta value always wins; when it
	 * is unset the Preselect setting decides, but only for editor/REST saves so
	 * a programmatic/bulk import never unexpectedly generates audio.
	 */
	public static function should_generate_audio_for_post( int $post_id ): bool {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}

		// False when the post was deleted between a deferred job being queued and
		// the cron firing; bail before the strict-typed status check.
		$status = get_post_status( $post_id );
		if ( ! $status ) {
			return false;
		}

		if ( ! self::should_process_post_status( $status ) ) {
			return false;
		}

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
	 * Whether audio (re)generation runs in the background instead of blocking the save.
	 *
	 * VIP-only: Cron Control runs scheduled events reliably there; elsewhere
	 * WP-Cron is traffic-triggered, so we stay synchronous. See doc/async-rest-migration.md.
	 *
	 * @since 7.0.0
	 */
	public static function is_async_generation_enabled(): bool {
		// These symbols only exist on WordPress VIP.
		$enabled = class_exists( '\Automattic\WP\Cron_Control\Main' )
			|| function_exists( 'wpcom_vip_schedule_single_event' )
			|| defined( 'VIP_GO_APP_ENVIRONMENT' );

		/**
		 * Filters whether BeyondWords audio (re)generation runs in the background.
		 *
		 * Defaults to true only on WordPress VIP.
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
	 * @param int $post_id WordPress post ID.
	 *
	 * @return array<mixed>|false|null Response from the API, or false when audio wasn't generated.
	 */
	public static function generate_audio_for_post( int $post_id ): array|false|null {
		if ( ! self::should_generate_audio_for_post( $post_id ) ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$integration_method = \BeyondWords\Settings\Fields::get_integration_method( $post );

		// Client-side integration is "Magic Embed": import via the by-source-id endpoint.
		if ( \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE === $integration_method ) {
			update_post_meta( $post_id, 'beyondwords_integration_method', \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE );
			update_post_meta( $post_id, 'beyondwords_project_id', get_option( 'beyondwords_project_id' ) );

			return \BeyondWords\Api\Client::get_player_by_source_id( $post_id );
		}

		update_post_meta( $post_id, 'beyondwords_integration_method', \BeyondWords\Settings\Fields::INTEGRATION_REST_API );

		$content_id = \BeyondWords\Post\Meta::get_content_id( $post_id );

		if ( $content_id ) {
			if ( defined( 'BEYONDWORDS_AUTOREGENERATE' ) && ! BEYONDWORDS_AUTOREGENERATE ) {
				return false;
			}

			$response = self::update_or_recreate_audio( $post_id );
		} else {
			$response = \BeyondWords\Api\Client::create_audio( $post_id );
		}

		$project_id = \BeyondWords\Post\Meta::get_project_id( $post_id );
		self::process_response( $response, $project_id, $post_id );

		return $response;
	}

	/**
	 * Update audio for a post, recovering from a stale content ID.
	 *
	 * A `#404:…` error meta means the content no longer exists at BeyondWords,
	 * so clear the stale IDs and create fresh content instead.
	 *
	 * @param int $post_id WordPress post ID.
	 */
	private static function update_or_recreate_audio( int $post_id ): array|null|false {
		$response = \BeyondWords\Api\Client::update_audio( $post_id );

		$error_message = (string) get_post_meta( $post_id, 'beyondwords_error_message', true );

		if ( str_starts_with( $error_message, '#404:' ) ) {
			delete_post_meta( $post_id, 'beyondwords_content_id' );
			delete_post_meta( $post_id, 'beyondwords_podcast_id' );
			delete_post_meta( $post_id, 'speechkit_podcast_id' );

			$response = \BeyondWords\Api\Client::create_audio( $post_id );
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
	 * Dispatch bulk "Generate audio" for a set of posts.
	 *
	 * On VIP each post is queued as a background cron job; off VIP generation
	 * runs inline, capped at BULK_GENERATE_SYNC_LIMIT with the rest deferred.
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

		// The async cron job reads this flag; off VIP it records intent for the
		// posts deferred past the cap.
		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, 'beyondwords_generate_audio', '1' );
		}

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

		// Each call is bounded by the client's short timeout, so the cap is what
		// keeps the batch total inside execution limits.
		$ordered    = self::order_posts_for_bulk_generation( $post_ids );
		$limit      = self::BULK_GENERATE_SYNC_LIMIT;
		$to_process = array_slice( $ordered, 0, $limit );
		$deferred   = count( $ordered ) - count( $to_process );

		$generated = 0;
		$failed    = 0;

		foreach ( $to_process as $post_id ) {
			if ( self::generate_audio_for_post( $post_id ) ) {
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
	 * Order a bulk selection so posts needing audio precede regenerations.
	 *
	 * Keeps the synchronous cap making forward progress: re-running the action
	 * works through un-generated posts instead of re-updating the same ones.
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
	 * Run a deferred audio deletion queued by the trash/delete handlers.
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

			// Deliberately don't copy `language`/`body_voice_id` back: those keys hold
			// explicit editor choices, and echoing the API-resolved project defaults
			// would freeze them. See Content::get_content_params().
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
	 * Only keys the block editor reads/writes over REST get `show_in_rest`;
	 * secrets and internal data never reach the REST API.
	 */
	public static function register_meta(): void {
		$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

		if ( ! is_array( $post_types ) ) {
			return;
		}

		$keys = \BeyondWords\Core\Utils::get_post_meta_keys( 'all' );

		$rest_keys = array_merge(
			\BeyondWords\Core\Utils::get_post_meta_keys( 'current' ),
			self::REST_LEGACY_META_KEYS
		);

		foreach ( $post_types as $post_type ) {
			foreach ( $keys as $key ) {
				// Content IDs are interpolated into API URL paths, so REST writes need
				// the same strict validation as the classic editor.
				$sanitize_callback = 'beyondwords_content_id' === $key
					? [ \BeyondWords\Post\Meta::class, 'sanitize_content_id' ]
					: 'sanitize_text_field';

				register_meta(
					'post',
					$key,
					[
						'show_in_rest'      => in_array( $key, $rest_keys, true ),
						'single'            => true,
						'type'              => 'string',
						'default'           => '',
						'object_subtype'    => $post_type,
						'prepare_callback'  => 'sanitize_text_field',
						'sanitize_callback' => $sanitize_callback,
						'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
					]
				);
			}
		}
	}

	/**
	 * Register the REST filter that hides private BeyondWords meta, per post type.
	 *
	 * @since 7.0.0
	 */
	public static function register_rest_meta_visibility(): void {
		$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

		if ( ! is_array( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $post_type ) {
			add_filter( "rest_prepare_{$post_type}", [ self::class, 'hide_private_meta_from_rest' ], 10, 3 );
		}
	}

	/**
	 * Strip secret/internal BeyondWords meta from non-`edit` REST responses.
	 *
	 * `WP_REST_Meta_Fields` returns `show_in_rest` meta in the public `view`
	 * context with no capability check; the `edit` context is permission-gated.
	 *
	 * @since 7.0.0
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_Post          $post     The post the response is for.
	 * @param \WP_REST_Request  $request  The request object.
	 *
	 * @return \WP_REST_Response
	 */
	public static function hide_private_meta_from_rest( $response, $post, $request ) {
		if ( ! $response instanceof \WP_REST_Response ) {
			return $response;
		}

		if ( 'edit' === $request->get_param( 'context' ) ) {
			return $response;
		}

		$data = $response->get_data();

		if ( ! is_array( $data ) || empty( $data['meta'] ) || ! is_array( $data['meta'] ) ) {
			return $response;
		}

		foreach ( self::REST_PRIVATE_META_KEYS as $key ) {
			unset( $data['meta'][ $key ] );
		}

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Hide BeyondWords meta from the legacy "Custom Fields" panel.
	 *
	 * The panel can break when our meta renders alongside the auto-rendered
	 * controls — https://github.com/WordPress/gutenberg/issues/23078.
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
	 * No-ops when an event for this post is already queued, so repeated saves
	 * don't stack duplicate jobs.
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
	 * Mirrors `schedule_audio_generation()`, including the duplicate-event guard.
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
	 * Clear any pending audio-generation cron event for a post.
	 *
	 * Runs before the `has_content()` checks in the lifecycle handlers because
	 * a queued post may not have written its content meta yet.
	 *
	 * @since 7.0.0
	 */
	private static function unschedule_audio_generation( int $post_id ): void {
		wp_clear_scheduled_hook( self::GENERATE_AUDIO_CRON_HOOK, [ $post_id ] );
	}

	/**
	 * Delete a post's BeyondWords audio, deferring to background cron on VIP.
	 *
	 * The IDs are captured now because the caller wipes the meta (trash) or
	 * WordPress deletes the row (permanent delete) before a deferred job runs.
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
	 * Trash hook: delete the remote audio, then remove our local metadata.
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
	 * Permanent delete hook: same as trash, minus the meta cleanup.
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
	 * Skips Gutenberg's second invocation via the meta-box save round-trip,
	 * which would otherwise double-process every save.
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

		// Eligibility is re-checked inside generate_audio_for_post() when the
		// deferred job runs.
		if ( self::is_async_generation_enabled() && self::should_generate_audio_for_post( $post_id ) ) {
			self::schedule_audio_generation( $post_id );

			return true;
		}

		return (bool) self::generate_audio_for_post( $post_id );
	}

	/**
	 * Back-fill `beyondwords_language_code` from the legacy numeric language ID.
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
