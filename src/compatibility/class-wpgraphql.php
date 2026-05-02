<?php
/**
 * WPGraphQL compatibility.
 *
 * Exposes BeyondWords audio metadata as a GraphQL type and field on every
 * BeyondWords-compatible post type.
 *
 * @package BeyondWords\Compatibility
 * @since   3.6.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

declare( strict_types = 1 );

namespace BeyondWords\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Expose BeyondWords fields in WPGraphQL.
 *
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
class WPGraphQL {

	/**
	 * Register WordPress hooks.
	 */
	public static function init(): void {
		add_action( 'graphql_register_types', [ self::class, 'graphql_register_types' ] );
	}

	/**
	 * Register the BeyondWords GraphQL type and attach it to compatible post types.
	 */
	public static function graphql_register_types(): void {
		register_graphql_object_type(
			'Beyondwords',
			[
				'description' => __( 'BeyondWords audio details. Use this data to embed an audio player using the BeyondWords JavaScript SDK.', 'speechkit' ),
				'fields'      => [
					'sourceId'  => [
						'description' => __( 'BeyondWords source ID', 'speechkit' ),
						'type'        => 'String',
					],
					'projectId' => [
						'description' => __( 'BeyondWords project ID', 'speechkit' ),
						'type'        => 'Int',
					],
					'contentId' => [
						'description' => __( 'BeyondWords content ID', 'speechkit' ),
						'type'        => 'String',
					],
					'podcastId' => [
						'description' => __( 'BeyondWords legacy podcast ID', 'speechkit' ),
						'type'        => 'String',
					],
				],
			]
		);

		$bw_post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();
		$graphql_post_types     = \WPGraphQL::get_allowed_post_types();
		$post_types             = array_intersect( $bw_post_types, $graphql_post_types );

		foreach ( $post_types as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );

			if ( ! $post_type_object ) {
				continue;
			}

			register_graphql_field(
				$post_type_object->graphql_single_name,
				'beyondwords',
				[
					'type'        => 'Beyondwords',
					'description' => __( 'BeyondWords audio details', 'speechkit' ),
					'resolve'     => static function ( \WPGraphQL\Model\Post $post ) {
						$fields = [
							'sourceId' => (string) $post->ID,
						];

						$project_id = \BeyondWords\Post\PostMetaUtils::get_project_id( $post->ID );
						if ( ! empty( $project_id ) ) {
							$fields['projectId'] = $project_id;
						}

						$content_id = \BeyondWords\Post\PostMetaUtils::get_content_id( $post->ID );
						if ( ! empty( $content_id ) ) {
							$fields['contentId'] = $content_id;
							$fields['podcastId'] = $content_id;
						}

						return $fields;
					},
				]
			);
		}
	}
}
