<?php

declare( strict_types = 1 );

namespace Beyondwords\Wordpress\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Post meta operations for BeyondWords import records.
 *
 * Centralises meta key names and read/write logic.
 *
 * @since 1.0.0
 */
class PostMeta {
	const KEY_GENERATE_AUDIO     = 'beyondwords_generate_audio';
	const KEY_PROJECT_ID         = 'beyondwords_project_id';
	const KEY_CONTENT_ID         = 'beyondwords_content_id';
	const KEY_INTEGRATION_METHOD = 'beyondwords_integration_method';

	/**
	 * Write the BeyondWords meta fields for a single post.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Also records the integration method.
	 *
	 * @param int   $post_id The WordPress post ID.
	 * @param array $record  The import record.
	 */
	public static function update_for_record( $post_id, $record ) {
		update_post_meta( $post_id, self::KEY_GENERATE_AUDIO, '1' );
		update_post_meta( $post_id, self::KEY_PROJECT_ID, intval( $record['project_id'] ) );
		update_post_meta( $post_id, self::KEY_CONTENT_ID, sanitize_text_field( $record['content_id'] ) );

		// A Content ID is only read under the REST API integration; without this
		// a Magic Embed site resolves the player by source ID and ignores it.
		update_post_meta( $post_id, self::KEY_INTEGRATION_METHOD, Compat::get_integration_method() );
	}

	/**
	 * Verify that BeyondWords meta fields were persisted correctly.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Also verifies the integration method.
	 *
	 * @param int   $post_id The WordPress post ID.
	 * @param array $record  The import record.
	 *
	 * @return bool True if all values match, false otherwise.
	 */
	public static function verify_for_record( $post_id, $record ) {
		return (
			get_post_meta( $post_id, self::KEY_GENERATE_AUDIO, true ) === '1' &&
			(int) get_post_meta( $post_id, self::KEY_PROJECT_ID, true ) === intval( $record['project_id'] ) &&
			get_post_meta( $post_id, self::KEY_CONTENT_ID, true ) === sanitize_text_field( $record['content_id'] ) &&
			get_post_meta( $post_id, self::KEY_INTEGRATION_METHOD, true ) === Compat::get_integration_method()
		);
	}
}
