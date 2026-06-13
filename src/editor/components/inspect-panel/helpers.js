/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

// The current (v7) BeyondWords custom fields, in the order the Inspect panel
// lists and copies them.
export const CURRENT_META_KEYS = [
	'beyondwords_generate_audio',
	'beyondwords_project_id',
	'beyondwords_content_id',
	'beyondwords_integration_method',
	'beyondwords_preview_token',
	'beyondwords_language_code',
	'beyondwords_language_id',
	'beyondwords_body_voice_id',
	'beyondwords_error_message',
	'beyondwords_delete_content',
	'beyondwords_source',
	'beyondwords_output',
	'beyondwords_script_template_id',
	'beyondwords_video_template_id',
	'beyondwords_video_size',
	'beyondwords_embed',
];

// Custom fields kept only for backwards compatibility with pre-v7 posts.
export const DEPRECATED_META_KEYS = [
	'beyondwords_podcast_id',
	'publish_post_to_speechkit',
	'speechkit_generate_audio',
	'speechkit_project_id',
	'speechkit_podcast_id',
	'speechkit_error_message',
	'speechkit_disabled',
	'speechkit_access_key',
	'speechkit_error',
	'speechkit_info',
	'speechkit_response',
	'speechkit_retries',
	'speechkit_status',
	'_speechkit_link',
	'_speechkit_text',
];

// Diagnostics added to the copied payload only. These are always present (the
// plugin/WP versions and the post id), so they must NOT count as removable post
// data when deciding whether the Remove button should be enabled.
export const SYSTEM_META_KEYS = [
	'plugin_version',
	'wp_version',
	'wp_post_id',
];

/**
 * Does the post hold any BeyondWords data worth removing?
 *
 * Derived from the live data fields only (current + deprecated), never the
 * always-present system fields, so the Remove button tracks the post's current
 * state rather than a snapshot taken at mount.
 *
 * @param {Object} meta Live custom-field values keyed by field name.
 * @return {boolean} Whether any data field has a non-empty value.
 */
export const hasBeyondwordsData = ( meta ) =>
	[ ...CURRENT_META_KEYS, ...DEPRECATED_META_KEYS ].some(
		( key ) => !! meta[ key ]?.length
	);

/**
 * Build the plain-text diagnostics payload for the Copy button.
 *
 * Reads the same live `meta` object as {@link hasBeyondwordsData} so the Copy
 * and Remove controls can never disagree about the post's data.
 *
 * @param {Object} meta Live custom-field values keyed by field name.
 * @return {string} The clipboard payload.
 */
export const getTextToCopy = ( meta ) => {
	const line = ( key ) => `${ key }\r\n${ meta[ key ] }`;

	return (
		[
			...CURRENT_META_KEYS.map( line ),
			`=== ${ __( 'Deprecated', 'speechkit' ) } ===`,
			...DEPRECATED_META_KEYS.map( line ),
			`=== ${ __( 'System', 'speechkit' ) } ===`,
			...SYSTEM_META_KEYS.map( line ),
			`=== ${ __( 'Copied using the Block Editor', 'speechkit' ) } ===`,
		].join( '\r\n\r\n' ) + '\r\n\r\n'
	);
};
