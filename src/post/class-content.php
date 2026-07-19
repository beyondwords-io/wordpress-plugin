<?php

declare( strict_types = 1 );

namespace BeyondWords\Post;

/**
 * BeyondWords Post Content Utilities.
 *
 * @package    Beyondwords
 * @subpackage Beyondwords/includes
 * @author     Stuart McAlpine <stu@beyondwords.io>
 * @since      3.5.0
 * @since      7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class Content {

	public const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

	/**
	 * Get the content "body" param for the audio.
	 *
	 * The excerpt is prepended to the body because API v1.1 repurposed the
	 * "summary" param.
	 *
	 * @since 4.6.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int|\WP_Post $post The WordPress post ID, or post object.
	 *
	 * @return string The content body param.
	 */
	public static function get_content_body( int|\WP_Post $post ): string|null {
		$post = get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			throw new \Exception( esc_html__( 'Post Not Found', 'speechkit' ) );
		}

		$summary = self::get_post_summary( $post );
		$body    = self::get_post_body( $post );

		if ( $summary ) {
			$format = self::get_post_summary_wrapper_format( $post );

			$body = sprintf( $format, $summary ) . $body;
		}

		return $body;
	}

	/**
	 * Get the post body for the audio content.
	 *
	 * @since 3.0.0
	 * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
	 * @since 3.8.0 Exclude Gutenberg blocks with attribute { beyondwordsAudio: false }
	 * @since 4.0.0 Renamed from Content::getSourceTextForAudio() to Content::getBody()
	 * @since 4.6.0 Renamed from Content::getBody() to Content::get_post_body()
	 * @since 4.7.0 Remove wpautop filter for block editor API requests.
	 * @since 5.0.0 Remove SpeechKit-Start shortcode.
	 * @since 5.0.0 Remove beyondwords_content filter.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int|\WP_Post $post The WordPress post ID, or post object.
	 *
	 * @return string The body (the processed $post->post_content).
	 */
	public static function get_post_body( int|\WP_Post $post ): string|null {
		$post = get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			throw new \Exception( esc_html__( 'Post Not Found', 'speechkit' ) );
		}

		$content = self::get_content_without_excluded_blocks( $post );

		if ( has_blocks( $post ) ) {
			// wpautop breaks our HTML markup when block editor paragraphs are empty,
			// but we still want to remove the empty lines it would have handled.
			remove_filter( 'the_content', 'wpautop' );

			$content = preg_replace( '/^\h*\v+/m', '', $content );
		}

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Applying core WordPress filter
		$content = apply_filters( 'the_content', $content );

		return trim( $content );
	}

	/**
	 * Get the post summary wrapper format.
	 *
	 * @since 4.6.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int|\WP_Post $post The WordPress post ID, or post object.
	 *
	 * @return string The summary wrapper <div>.
	 */
	public static function get_post_summary_wrapper_format( int|\WP_Post $post ): string {
		$post = get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			throw new \Exception( esc_html__( 'Post Not Found', 'speechkit' ) );
		}

		return '<div data-beyondwords-summary="true">%s</div>';
	}

	/**
	 * Get the post summary for the audio content.
	 *
	 * @since 4.0.0
	 * @since 4.6.0 Renamed from Content::getSummary() to Content::get_post_summary()
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int|\WP_Post $post The WordPress post ID, or post object.
	 *
	 * @return string The summary.
	 */
	public static function get_post_summary( int|\WP_Post $post ): string|null {
		$post = get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			throw new \Exception( esc_html__( 'Post Not Found', 'speechkit' ) );
		}

		$summary = null;

		$prepend_excerpt = get_option( 'beyondwords_prepend_excerpt' );

		if ( $prepend_excerpt && has_excerpt( $post ) ) {
			$summary = htmlentities( $post->post_excerpt, ENT_QUOTES | ENT_XHTML );
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Applying core WordPress filter
			$summary = apply_filters( 'get_the_excerpt', $summary );
			$summary = trim( wpautop( $summary ) );
		}

		return $summary;
	}

	/**
	 * Get the post content without the blocks an editor excluded from audio.
	 *
	 * @since 3.8.0
	 * @since 4.0.0 Replace for loop with array_reduce
	 * @since 6.0.0 Remove beyondwordsMarker attribute from rendered blocks.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int|\WP_Post $post The WordPress post ID, or post object.
	 *
	 * @return string The post body without excluded blocks.
	 */
	public static function get_content_without_excluded_blocks( int|\WP_Post $post ): string {
		$post = get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			throw new \Exception( esc_html__( 'Post Not Found', 'speechkit' ) );
		}

		if ( ! has_blocks( $post ) ) {
			return trim( $post->post_content );
		}

		$output = '';

		$blocks = self::get_audio_enabled_blocks( $post );

		foreach ( $blocks as $block ) {
			$output .= render_block( $block );
		}

		return $output;
	}

	/**
	 * Get audio-enabled blocks.
	 *
	 * @since 4.0.0
	 * @since 5.0.0 Remove beyondwords_post_audio_enabled_blocks filter.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int|\WP_Post $post The WordPress post ID, or post object.
	 *
	 * @return array The blocks.
	 */
	public static function get_audio_enabled_blocks( int|\WP_Post $post ): array {
		$post = get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return [];
		}

		if ( ! has_blocks( $post ) ) {
			return [];
		}

		$all_blocks = parse_blocks( $post->post_content );

		return array_filter(
			$all_blocks,
			function ( $block ) {
				$enabled = true;

				if ( is_array( $block['attrs'] ) && isset( $block['attrs']['beyondwordsAudio'] ) ) {
					$enabled = (bool) $block['attrs']['beyondwordsAudio'];
				}

				return $enabled;
			}
		);
	}

	/**
	 * Get the body param we pass to the API.
	 *
	 * @since 3.0.0  Introduced as getBodyJson.
	 * @since 3.3.0  Added metadata to aid custom playlist generation.
	 * @since 3.5.0  Moved from Core\Utils to Component\Post\PostUtils.
	 * @since 3.10.4 Rename `published_at` API param to `publish_date`.
	 * @since 4.0.0  Use new API params.
	 * @since 4.0.3  Ensure `image_url` is always a string.
	 * @since 4.3.0  Rename from getBodyJson to getContentParams.
	 * @since 4.6.0  Remove summary param & prepend body with summary.
	 * @since 5.0.0  Remove beyondwords_body_params filter.
	 * @since 6.0.0  Cast return value to string.
	 *
	 * @static
	 * @param int $post_id WordPress Post ID.
	 *
	 * @return string JSON encoded params.
	 **/
	public static function get_content_params( int $post_id ): array|string {
		$body = [
			'type'         => 'auto_segment',
			'title'        => get_the_title( $post_id ),
			'body'         => self::get_content_body( $post_id ),
			'source_url'   => get_the_permalink( $post_id ),
			'source_id'    => strval( $post_id ),
			'author'       => self::get_author_name( $post_id ),
			'image_url'    => strval( wp_get_original_image_url( get_post_thumbnail_id( $post_id ) ) ),
			'metadata'     => self::get_metadata( $post_id ),
			'publish_date' => get_post_time( self::DATE_FORMAT, true, $post_id ),
		];

		$status = get_post_status( $post_id );

		// Drafts send { published: false } to keep the audio out of playlists, and
		// omit publish_date because get_post_time() is false for pending posts.
		if ( in_array( $status, [ 'draft', 'pending'] ) ) {
			$body['published'] = false;
			unset( $body['publish_date'] );
		} else {
			/**
			 * Filters whether generated content is auto-published to BeyondWords.
			 *
			 * Replaces the v6.x `beyondwords_project_auto_publish_enabled` setting.
			 *
			 * @since 7.0.0
			 *
			 * @param bool $auto_publish Whether to mark generated content as published.
			 * @param int  $post_id       WordPress post ID.
			 */
			$auto_publish = apply_filters( 'beyondwords_auto_publish', true, $post_id );

			if ( $auto_publish ) {
				$body['published'] = true;
			}
		}

		// The language is never sent: a chosen voice implies it, and with no voice
		// the project default applies. `beyondwords_language_code` is editor state only.
		$body_voice_id = intval( get_post_meta( $post_id, 'beyondwords_body_voice_id', true ) );

		if ( $body_voice_id > 0 ) {
			$body['body_voice_id'] = $body_voice_id;
		}

		// Source = Script or Post + script → enable summarization; omitted when
		// Source is Post (or unset) so the project default applies.
		$source = get_post_meta( $post_id, 'beyondwords_source', true );

		if ( in_array( $source, [ 'script', 'post_and_script' ], true ) ) {
			$body['summarization_settings'] = [ 'enabled' => true ];

			$script_template_id = intval(
				get_post_meta( $post_id, 'beyondwords_script_template_id', true )
			);

			if ( $script_template_id > 0 ) {
				$body['summarization_settings']['template'] = [
					'id' => $script_template_id,
				];
			}
		}

		$output = get_post_meta( $post_id, 'beyondwords_output', true );

		if ( in_array( $output, [ 'video', 'audio_and_video' ], true ) ) {
			$body['video_settings'] = self::get_video_settings_params( $post_id );
		}

		/**
		 * Filters the params we send to the BeyondWords API 'content' endpoint.
		 *
		 * @since 4.0.0 Introduced as beyondwords_body_params
		 * @since 4.3.0 Renamed from beyondwords_body_params to beyondwords_content_params
		 *
		 * @param array $body   The params we send to the BeyondWords API.
		 * @param array $post_id WordPress post ID.
		 */
		$body = apply_filters( 'beyondwords_content_params', $body, $post_id );

		return (string) wp_json_encode( $body );
	}

	/**
	 * Build the `video_settings` param sent to the BeyondWords content endpoint.
	 *
	 * The backend silently skips video generation unless the payload has `enabled:
	 * true` plus non-empty `variants` and `sizes` (with dimensions), so we seed
	 * from the project defaults and layer the post's choices on top. See doc/video-settings-payload.md.
	 *
	 * @since 7.0.0
	 *
	 * @param int $post_id WordPress post ID.
	 *
	 * @return array<string, mixed> The `video_settings` param.
	 */
	private static function get_video_settings_params( int $post_id ): array {
		// The post's project may be a per-post override of the global one.
		$project_id = \BeyondWords\Post\Meta::get_project_id( $post_id );
		$defaults   = \BeyondWords\Api\Client::get_video_settings( is_numeric( $project_id ) ? (int) $project_id : null );
		$defaults   = is_array( $defaults ) ? $defaults : [];

		$settings = [ 'enabled' => true ];

		// The backend needs a non-empty `variants`; there is no per-post variant
		// control, so echo the project defaults.
		if ( ! empty( $defaults['variants'] ) && is_array( $defaults['variants'] ) ) {
			$settings['variants'] = array_values( $defaults['variants'] );
		}

		// Echo the project sizes (the backend requires width/height), enabling
		// only the post's chosen size when one is set.
		$video_size    = (string) get_post_meta( $post_id, 'beyondwords_video_size', true );
		$default_sizes = ( isset( $defaults['sizes'] ) && is_array( $defaults['sizes'] ) ) ? $defaults['sizes'] : [];

		$sizes = [];

		foreach ( $default_sizes as $size ) {
			if ( ! is_array( $size ) || ! isset( $size['name'] ) ) {
				continue;
			}

			$sizes[] = [
				'name'    => (string) $size['name'],
				'width'   => (int) ( $size['width'] ?? 0 ),
				'height'  => (int) ( $size['height'] ?? 0 ),
				'enabled' => '' !== $video_size
					? ( (string) $size['name'] === $video_size )
					: (bool) ( $size['enabled'] ?? false ),
			];
		}

		if ( ! empty( $sizes ) ) {
			$settings['sizes'] = $sizes;
		}

		// Omit `template` to defer to the project default.
		$video_template_id = intval( get_post_meta( $post_id, 'beyondwords_video_template_id', true ) );

		if ( $video_template_id > 0 ) {
			$settings['template'] = [ 'id' => $video_template_id ];
		}

		return $settings;
	}

	/**
	 * Get the post metadata to send with BeyondWords API requests.
	 *
	 * The values are used to create playlist filters in the BeyondWords dashboard.
	 *
	 * @since 3.3.0
	 * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils.
	 * @since 5.0.0 Remove beyondwords_post_metadata filter.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return object The metadata object (empty if no metadata).
	 */
	public static function get_metadata( int $post_id ): array|object {
		$metadata = new \stdClass();

		$taxonomy = self::get_all_taxonomies_and_terms( $post_id );

		if ( count( (array) $taxonomy ) ) {
			$metadata->taxonomy = $taxonomy;
		}

		return $metadata;
	}

	/**
	 * Get all taxonomies, and their selected terms, for a post.
	 *
	 * @since 3.3.0
	 * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return object The taxonomies object (empty if no taxonomies).
	 */
	public static function get_all_taxonomies_and_terms( int $post_id ): array|object {
		$post_type = get_post_type( $post_id );

		$post_type_taxonomies = get_object_taxonomies( $post_type );

		$taxonomies = new \stdClass();

		foreach ( $post_type_taxonomies as $post_type_taxonomy ) {
			$terms = get_the_terms( $post_id, $post_type_taxonomy );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$taxonomies->{ (string) $post_type_taxonomy} = wp_list_pluck( $terms, 'name' );
			}
		}

		return $taxonomies;
	}

	/**
	 * Get author name for a post.
	 *
	 * @since 3.10.4
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function get_author_name( int $post_id ): string {
		$author_id = get_post_field( 'post_author', $post_id );

		return get_the_author_meta( 'display_name', $author_id );
	}
}
