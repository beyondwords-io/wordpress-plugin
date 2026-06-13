/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

// Diagnostics appended to the copied payload only. These are always present (the
// plugin/WP versions and the post id), so they are never counted as removable
// post data when deciding whether the Remove button should be enabled.
//
// The current + deprecated *data* keys are NOT defined here on purpose — they are
// the single source of truth in PHP (\BeyondWords\Core\Utils::get_post_meta_keys)
// and reach the block editor via the beyondwords/settings store, so the two
// editors can never drift apart.
export const SYSTEM_META_KEYS = [
	'plugin_version',
	'wp_version',
	'wp_post_id',
];

/**
 * Does the post hold any BeyondWords data worth removing?
 *
 * Checks the live data fields only (current + deprecated, supplied from PHP),
 * never the always-present system fields, so the Remove button tracks the post's
 * current state rather than a snapshot taken at mount.
 *
 * @param {Object}   meta     Live custom-field values keyed by field name.
 * @param {string[]} dataKeys Current + deprecated meta keys (sourced from PHP).
 * @return {boolean} Whether any data field has a non-empty value.
 */
export const hasBeyondwordsData = ( meta, dataKeys = [] ) =>
	dataKeys.some( ( key ) => !! meta[ key ]?.length );

/**
 * Build the plain-text diagnostics payload for the Copy button.
 *
 * Reads the same live `meta` object as {@link hasBeyondwordsData} so the Copy and
 * Remove controls can never disagree about the post's data. The key lists are
 * supplied by the caller (sourced from PHP) and rendered in the order given.
 *
 * @param {Object}   meta           Live values keyed by field name (incl. system).
 * @param {string[]} currentKeys    Current meta keys, in display order.
 * @param {string[]} deprecatedKeys Deprecated meta keys, in display order.
 * @return {string} The clipboard payload.
 */
export const getTextToCopy = (
	meta,
	currentKeys = [],
	deprecatedKeys = []
) => {
	const line = ( key ) => `${ key }\r\n${ meta[ key ] }`;

	return (
		[
			...currentKeys.map( line ),
			`=== ${ __( 'Deprecated', 'speechkit' ) } ===`,
			...deprecatedKeys.map( line ),
			`=== ${ __( 'System', 'speechkit' ) } ===`,
			...SYSTEM_META_KEYS.map( line ),
			`=== ${ __( 'Copied using the Block Editor', 'speechkit' ) } ===`,
		].join( '\r\n\r\n' ) + '\r\n\r\n'
	);
};
