<?php
/**
 * BeyondWords core post-lifecycle hooks.
 *
 * Owns the post-save / trash / delete handlers and the meta key registration.
 * Block-editor JS bootstrap lives in [src/editor/class-editor.php](src/editor/class-editor.php).
 *
 * @package BeyondWords\Core
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 *               Editor enqueue moved to BeyondWords\Editor\Editor.
 */

declare( strict_types = 1 );

namespace BeyondWords\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Post-lifecycle hooks.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class Core {

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'init', [ self::class, 'register_meta' ], 99, 3 );

		add_action( 'wp_after_insert_post', [ self::class, 'on_add_or_update_post' ], 99 );
		add_action( 'wp_trash_post', [ self::class, 'on_trash_post' ] );
		add_action( 'before_delete_post', [ self::class, 'on_delete_post' ] );

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
	 * Returns false for autosaves, revisions, ineligible statuses, and posts
	 * whose `beyondwords_generate_audio` flag is unset.
	 */
	public static function should_generate_audio_for_post( int $post_id ): bool {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}

		if ( ! self::should_process_post_status( get_post_status( $post_id ) ) ) {
			return false;
		}

		if ( \BeyondWords\Post\PostMetaUtils::has_generate_audio( $post_id ) ) {
			return (bool) get_post_meta( $post_id, 'beyondwords_generate_audio', true );
		}

		return false;
	}

	/**
	 * Generate audio for a post if eligibility checks pass.
	 *
	 * For Magic Embed (client-side) we POST to the by-source-id endpoint and
	 * stamp the post meta. For REST API integration we either update existing
	 * audio (with 404 recovery via `update_or_recreate_audio()`) or create
	 * fresh content.
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

		// Magic Embed: import via the source ID endpoint.
		if ( \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE === $integration_method ) {
			update_post_meta( $post_id, 'beyondwords_integration_method', \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE );
			update_post_meta( $post_id, 'beyondwords_project_id', get_option( 'beyondwords_project_id' ) );

			return \BeyondWords\Api\Client::get_player_by_source_id( $post_id );
		}

		// REST API integration: update or create.
		update_post_meta( $post_id, 'beyondwords_integration_method', \BeyondWords\Settings\Fields::INTEGRATION_REST_API );

		$content_id = \BeyondWords\Post\PostMetaUtils::get_content_id( $post_id );

		if ( $content_id ) {
			if ( defined( 'BEYONDWORDS_AUTOREGENERATE' ) && ! BEYONDWORDS_AUTOREGENERATE ) {
				return false;
			}

			$response = self::update_or_recreate_audio( $post_id );
		} else {
			$response = \BeyondWords\Api\Client::create_audio( $post_id );
		}

		$project_id = \BeyondWords\Post\PostMetaUtils::get_project_id( $post_id );
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

			$copy = [
				'preview_token'    => 'beyondwords_preview_token',
				'language'         => 'beyondwords_language_code',
				'title_voice_id'   => 'beyondwords_title_voice_id',
				'summary_voice_id' => 'beyondwords_summary_voice_id',
				'body_voice_id'    => 'beyondwords_body_voice_id',
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

		$keys = CoreUtils::get_post_meta_keys( 'all' );

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
	 * @param bool|null   $protected Whether the meta is currently flagged protected.
	 * @param string|null $meta_key  Meta key being checked. Null is passed by some core paths.
	 */
	public static function is_protected_meta( $protected, $meta_key ): bool {
		if ( null === $meta_key ) {
			return (bool) $protected;
		}

		if ( in_array( $meta_key, CoreUtils::get_post_meta_keys( 'all' ), true ) ) {
			return true;
		}

		return (bool) $protected;
	}

	/**
	 * Trash hook — DELETE the audio so it disappears from the dashboard, then
	 * remove our local metadata.
	 */
	public static function on_trash_post( $post_id ): void {
		$post_id = (int) $post_id;

		if ( ! \BeyondWords\Post\PostMetaUtils::has_content( $post_id ) ) {
			return;
		}

		\BeyondWords\Api\Client::delete_audio( $post_id );
		\BeyondWords\Post\PostMetaUtils::remove_all_beyondwords_metadata( $post_id );
	}

	/**
	 * Permanent delete hook — same DELETE call as trash, without the meta cleanup
	 * (the row is going away anyway).
	 */
	public static function on_delete_post( $post_id ): void {
		$post_id = (int) $post_id;

		if ( ! \BeyondWords\Post\PostMetaUtils::has_content( $post_id ) ) {
			return;
		}

		\BeyondWords\Api\Client::delete_audio( $post_id );
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
			\BeyondWords\Post\PostMetaUtils::remove_all_beyondwords_metadata( $post_id );
			return false;
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
