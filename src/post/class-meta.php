<?php

declare( strict_types = 1 );

namespace BeyondWords\Post;

/**
 * BeyondWords Post Meta (Custom Field) Utilities.
 *
 * @package    Beyondwords
 * @subpackage Beyondwords/includes
 * @author     Stuart McAlpine <stu@beyondwords.io>
 * @since      3.5.0
 * @since      7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class Meta {

	public const WP_ERROR_FORMAT = 'WP_Error [%s] %s';

	/**
	 * Get "renamed" Post Meta.
	 *
	 * Checks `beyondwords_*` then the legacy `speechkit_*` prefix, migrating
	 * legacy values to the new prefix on read.
	 *
	 * @since 3.7.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $name   Custom field name, without the prefix.
	 *
	 * @return string
	 */
	public static function get_renamed_post_meta( int $post_id, string $name ): mixed {
		if ( metadata_exists( 'post', $post_id, 'beyondwords_' . $name ) ) {
			return get_post_meta( $post_id, 'beyondwords_' . $name, true );
		}

		if ( metadata_exists( 'post', $post_id, 'speechkit_' . $name ) ) {
			$value = get_post_meta( $post_id, 'speechkit_' . $name, true );

			update_post_meta( $post_id, 'beyondwords_' . $name, $value );

			return $value;
		}

		return '';
	}

	/**
	 * Get the BeyondWords metadata for a Post.
	 *
	 * @since 4.1.0 Append 'beyondwords_version' and 'wordpress_version'.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function get_all_beyondwords_metadata( int $post_id ): array {
		global $wp_version;

		$keys_to_check = \BeyondWords\Core\Utils::get_post_meta_keys( 'all' );

		$metadata = has_meta( $post_id );

		$metadata = array_filter( $metadata, fn( $item ) => in_array( $item['meta_key'], $keys_to_check ) );

        // phpcs:disable WordPress.DB.SlowDBQuery
		array_push(
			$metadata,
			[
				'meta_id'    => null,
				'meta_key'   => 'beyondwords_version',
				'meta_value' => BEYONDWORDS__PLUGIN_VERSION,
			],
			[
				'meta_id'    => null,
				'meta_key'   => 'wordpress_version',
				'meta_value' => $wp_version,
			],
			[
				'meta_id'    => null,
				'meta_key'   => 'wordpress_post_id',
				'meta_value' => $post_id,
			],
		);
        // phpcs:enable WordPress.DB.SlowDBQuery

		return $metadata;
	}

	/**
	 * Remove the BeyondWords metadata for a Post.
	 *
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @since 4.x   Introduced.
	 * @since 6.0.1 Use \BeyondWords\Core\Utils::get_post_meta_keys() to get all keys.
	 */
	public static function remove_all_beyondwords_metadata( int $post_id ): void {
		$keys = \BeyondWords\Core\Utils::get_post_meta_keys( 'all' );

		foreach ( $keys as $key ) {
			delete_post_meta( $post_id, $key, null );
		}
	}

	/**
	 * Check if a Post should have BeyondWords content (a Content entity in BeyondWords).
	 *
	 * @since 6.0.0 Introduced.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool True if the post should have BeyondWords content, false otherwise.
	 */
	public static function has_content( int $post_id ): bool {
		$content_id         = self::get_content_id( $post_id );
		$integration_method = get_post_meta( $post_id, 'beyondwords_integration_method', true );

		// Unset means REST API, for legacy compatibility.
		if ( empty( $integration_method ) ) {
			$integration_method = \BeyondWords\Settings\Fields::INTEGRATION_REST_API;
		}

		if ( \BeyondWords\Settings\Fields::INTEGRATION_REST_API === $integration_method && ! empty( $content_id ) ) {
			return true;
		}

		// Strict: don't fall back to the plugin-setting project ID.
		$project_id = self::get_project_id( $post_id, true );

		if ( \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE === $integration_method && ! empty( $project_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the Content ID for a WordPress Post.
	 *
	 * Over time there have been various approaches to storing the Content ID.
	 * This function tries each approach in reverse-date order.
	 *
	 * @since 3.0.0
	 * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
	 * @since 4.0.0 Renamed to getContentId() & prioritise beyondwords_content_id
	 * @since 5.0.0 Remove beyondwords_content_id filter.
	 * @since 6.0.0 Add fallback parameter to allow falling back to Post ID.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $fallback If true, will fall back to the Post ID if no Content ID is found.
	 *
	 * @return string|false Content ID, or false
	 */
	public static function get_content_id( int $post_id, bool $fallback = false ): string|int|false {
		$content_id = get_post_meta( $post_id, 'beyondwords_content_id', true );
		if ( ! empty( $content_id ) ) {
			return $content_id;
		}

		$podcast_id = self::get_podcast_id( $post_id );
		if ( ! empty( $podcast_id ) ) {
			return $podcast_id;
		}

		if ( $fallback ) {
			return (string) $post_id;
		}

		return false;
	}

	/**
	 * Sanitize a BeyondWords Content ID (numeric ID or UUID).
	 *
	 * Anything beyond alphanumerics and hyphens is rejected: the ID is later
	 * interpolated into API URL paths, where `/`, `?`, `#` etc. would allow
	 * path/query injection against the authenticated API.
	 *
	 * @since 7.0.0
	 *
	 * @param mixed $content_id Raw submitted Content ID.
	 *
	 * @return string Sanitized Content ID, or '' when the value is invalid.
	 */
	public static function sanitize_content_id( $content_id ): string {
		$content_id = sanitize_text_field( (string) $content_id );

		return preg_match( '/^[a-zA-Z0-9-]*$/', $content_id ) ? $content_id : '';
	}

	/**
	 * Get the (legacy) Podcast ID for a WordPress Post.
	 *
	 * Over time there have been various approaches to storing the Podcast ID.
	 * This function tries each approach in reverse-date order.
	 *
	 * @since 3.0.0
	 * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
	 * @since 4.0.0 Allow string values for UUIDs stored >= v4.x
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return int|false Podcast ID, or false
	 */
	public static function get_podcast_id( int $post_id ): string|int|false {
		$podcast_id = self::get_renamed_post_meta( $post_id, 'podcast_id' );

		if ( $podcast_id ) {
			return $podcast_id;
		}

		// Legacy player URLs are /a/[ID], /e/[ID] or /m/[ID].
		$speechkit_link = get_post_meta( $post_id, '_speechkit_link', true );
		preg_match( '/\/[aem]\/(\d+)/', (string) $speechkit_link, $matches );
		if ( $matches ) {
			return intval( $matches[1] );
		}

		$speechkit_response = self::get_http_response_body_from_post_meta( $post_id, 'speechkit_response' );
		preg_match( '/"podcast_id":(")?(\d+)(?(1)\1|)/', (string) $speechkit_response, $matches );
		if ( $matches && $matches[2] ) {
			return intval( $matches[2] );
		}

		// Mirrors the if/else at legacy Speechkit_Public::iframe_player_embed_html();
		// only the share_url branch produces a usable Podcast ID for us.
		$article = get_post_meta( $post_id, 'speechkit_info', true );
		if ( ! empty( $article ) && isset( $article['share_url'] ) ) {
			preg_match( '/\/[aem]\/(\d+)/', (string) $article['share_url'], $matches );
			if ( $matches ) {
				return intval( $matches[1] );
			}
		}

		return false;
	}

	/**
	 * Get the BeyondWords preview token for a WordPress Post.
	 *
	 * The token allows previewing audio in admin before its scheduled publish date.
	 *
	 * @since 4.5.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string Preview token
	 */
	public static function get_preview_token( int $post_id ): string|false {
		$preview_token = get_post_meta( $post_id, 'beyondwords_preview_token', true );

		return $preview_token ?: false;
	}

	/**
	 * Get the 'Generate Audio' value for a Post.
	 *
	 * @since 3.0.0
	 * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
	 * @since 6.0.0 Add Magic Embed support.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function has_generate_audio( int $post_id ): bool {
		$generate_audio = self::get_renamed_post_meta( $post_id, 'generate_audio' );

		if ( $generate_audio === '1' ) {
			return true;
		}

		if ( $generate_audio === '0' ) {
			return false;
		}

		return \BeyondWords\Editor\Components\GenerateAudio::should_preselect_generate_audio( $post_id );
	}

	/**
	 * Get the Project ID for a WordPress Post.
	 *
	 * The plugin-setting project ID may have changed since a post was created,
	 * so historic custom fields are checked before falling back to the setting.
	 *
	 * @since 3.0.0
	 * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
	 * @since 4.0.0 Apply beyondwords_project_id filter
	 * @since 5.0.0 Remove beyondwords_project_id filter.
	 * @since 6.0.0 Support Magic Embed and add strict mode.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $strict Strict mode, which only checks the custom field. Defaults to false.
	 *
	 * @return int|false Project ID, or false
	 */
	public static function get_project_id( int $post_id, bool $strict = false ): int|string|false {
		if ( $strict ) {
			return self::get_renamed_post_meta( $post_id, 'project_id' );
		}

		$post_meta = intval( self::get_renamed_post_meta( $post_id, 'project_id' ) );

		if ( ! empty( $post_meta ) ) {
			return $post_meta;
		}

		$speechkit_response = self::get_http_response_body_from_post_meta( $post_id, 'speechkit_response' );

		preg_match( '/"project_id":(")?(\d+)(?(1)\1|)/', (string) $speechkit_response, $matches );

		if ( $matches && $matches[2] ) {
			return intval( $matches[2] );
		}

		$setting = get_option( 'beyondwords_project_id' );

		if ( $setting ) {
			return intval( $setting );
		}

		return false;
	}

	/**
	 * Get the Body Voice ID for a WordPress Post.
	 *
	 * We do not filter this, because the Block Editor directly accesses this
	 * custom field, bypassing any filters we add here.
	 *
	 * @since 4.0.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return int|false Body Voice ID, or false
	 */
	public static function get_body_voice_id( int $post_id ): int|string|false {
		$voice_id = get_post_meta( $post_id, 'beyondwords_body_voice_id', true );

		return $voice_id ?: false;
	}

	/**
	 * Get the "Error Message" value for a WordPress Post.
	 *
	 * @since 3.7.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	public static function get_error_message( int $post_id ): string|false {
		return self::get_renamed_post_meta( $post_id, 'error_message' );
	}

	/**
	 * Get the "Disabled" value for a WordPress Post.
	 *
	 * @since 3.7.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function get_disabled( int $post_id ): bool {
		return (bool) self::get_renamed_post_meta( $post_id, 'disabled' );
	}

	/**
	 * Get HTTP response body from post meta.
	 *
	 * Legacy rows may hold a whole WordPress HTTP response array or a WP_Error;
	 * both are normalised to a string.
	 *
	 * @since 3.0.3
	 * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
	 * @since 3.6.1 Handle responses saved as object of class WP_Error
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $meta_name Post Meta name.
	 *
	 * @return string
	 */
	public static function get_http_response_body_from_post_meta( int $post_id, string $meta_name ): array|string|false {
		$post_meta = get_post_meta( $post_id, $meta_name, true );

		if ( is_array( $post_meta ) ) {
			return (string) wp_remote_retrieve_body( $post_meta );
		}

		if ( is_wp_error( $post_meta ) ) {
			return sprintf( self::WP_ERROR_FORMAT, $post_meta->get_error_code(), $post_meta->get_error_message() );
		}

		return (string) $post_meta;
	}
}
