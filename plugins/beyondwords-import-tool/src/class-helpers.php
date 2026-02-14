<?php

declare( strict_types=1 );

namespace Beyondwords\Wordpress\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper utilities for the import tool.
 *
 * @since 1.0.0
 */
class Helpers {
	/**
	 * Resolve a record to a WordPress post ID.
	 *
	 * If the source_id is a numeric post ID, return it directly.
	 * Otherwise, attempt to look up the post ID from the source_url.
	 *
	 * @since 1.0.0
	 *
	 * @param array $record A single import record.
	 *
	 * @return int|false The post ID, or false if it could not be resolved.
	 */
	public static function get_post_id_for_record( $record ) {
		if ( self::is_numeric_post_id( $record['source_id'] ) ) {
			return intval( $record['source_id'] );
		}

		// source_id is a UUID — try to find the post by its URL.
		if ( function_exists( 'wpcom_vip_url_to_postid' ) ) {
			$post_id = wpcom_vip_url_to_postid( $record['source_url'] );
		} else {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.url_to_postid_url_to_postid
			$post_id = url_to_postid( $record['source_url'] );
		}

		if ( $post_id > 0 ) {
			return $post_id;
		}

		return false;
	}

	/**
	 * Check if a source_id looks like a numeric post ID.
	 *
	 * Post IDs are numeric integers. Anything else (e.g. UUID v4) is not.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $source_id The source ID to check.
	 *
	 * @return bool
	 */
	public static function is_numeric_post_id( $source_id ) {
		return ctype_digit( (string) $source_id ) && intval( $source_id ) > 0;
	}

	/**
	 * Generate the preview code showing all update_post_meta() calls.
	 *
	 * @since 1.0.0
	 *
	 * @param array $import_data The parsed and validated import data.
	 *
	 * @return array { code: string, skipped: array }
	 */
	public static function generate_preview_code( $import_data ) {
		$lines   = [];
		$skipped = [];

		foreach ( $import_data as $record ) {
			$post_id = self::get_post_id_for_record( $record );

			if ( $post_id === false ) {
				$skipped[] = $record;
				continue;
			}

			// Verify the post exists.
			if ( ! get_post( $post_id ) ) {
				$skipped[] = $record;
				continue;
			}

			$comment = self::is_numeric_post_id( $record['source_id'] )
				? ''
				: sprintf( ' // resolved from %s', $record['source_url'] );

			$lines[] = sprintf(
				"update_post_meta(%s, '%s', '1');%s",
				$post_id,
				PostMeta::KEY_GENERATE_AUDIO,
				$comment
			);
			$lines[] = sprintf(
				"update_post_meta(%s, '%s', '%s');%s",
				$post_id,
				PostMeta::KEY_PROJECT_ID,
				intval( $record['project_id'] ),
				$comment
			);
			$lines[] = sprintf(
				"update_post_meta(%s, '%s', '%s');%s",
				$post_id,
				PostMeta::KEY_CONTENT_ID,
				sanitize_text_field( $record['content_id'] ),
				$comment
			);
		}

		return [
			'code'    => implode( "\n", $lines ),
			'skipped' => $skipped,
		];
	}
}
