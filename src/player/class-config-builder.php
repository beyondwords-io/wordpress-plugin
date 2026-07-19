<?php
/**
 * Builds the parameters object passed to the BeyondWords JS SDK.
 *
 * @package BeyondWords\Player
 * @since   6.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Player;

defined( 'ABSPATH' ) || exit;

/**
 * Constructs the SDK parameters object for one post.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class ConfigBuilder {

	/**
	 * Build the SDK parameters object for a post.
	 *
	 * @param \WP_Post $post WordPress post object.
	 *
	 * @return object Parameters for the JS SDK.
	 */
	public static function build( \WP_Post $post ): object {
		$project_id = \BeyondWords\Post\Meta::get_project_id( $post->ID );

		$params = [
			'projectId' => is_numeric( $project_id ) ? (int) $project_id : $project_id,
		];

		$params = self::merge_post_settings( $post, $params );

		return (object) apply_filters( 'beyondwords_player_sdk_params', $params, $post->ID );
	}

	/**
	 * Merge per-post overrides into the SDK parameters.
	 *
	 * For Magic Embed (client-side) integration without a content ID we flip
	 * to source-ID mode so the SDK fetches by source post ID instead.
	 *
	 * @param \WP_Post            $post   Post object.
	 * @param array<string,mixed> $params Existing params.
	 *
	 * @return array<string,mixed>
	 */
	public static function merge_post_settings( \WP_Post $post, array $params ): array {
		$content_id = \BeyondWords\Post\Meta::get_content_id( $post->ID );

		if ( ! empty( $content_id ) ) {
			$params['contentId'] = (string) $content_id;
		}

		if ( \BeyondWords\Settings\Fields::PLAYER_UI_HEADLESS === get_option( \BeyondWords\Settings\Fields::OPTION_PLAYER_UI ) ) {
			$params['showUserInterface'] = false;
		}

		// "Script" is the AI-summarization output, so script → summary:true.
		// None/audio_post add nothing — the SDK defaults to audio + body.
		$embed = \BeyondWords\Editor\Components\SettingsFields::get_effective_embed( $post->ID );

		switch ( $embed ) {
			case \BeyondWords\Editor\Components\SettingsFields::EMBED_AUDIO_SCRIPT:
				$params['summary'] = true;
				break;
			case \BeyondWords\Editor\Components\SettingsFields::EMBED_VIDEO_POST:
				$params['video'] = true;
				break;
			case \BeyondWords\Editor\Components\SettingsFields::EMBED_VIDEO_SCRIPT:
				$params['video']   = true;
				$params['summary'] = true;
				break;
		}

		$method = \BeyondWords\Settings\Fields::get_integration_method( $post );

		if ( \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE === $method && empty( $params['contentId'] ) ) {
			$params['clientSideEnabled'] = true;
			$params['sourceId']          = (string) $post->ID;
			unset( $params['contentId'] );
		}

		return $params;
	}
}
