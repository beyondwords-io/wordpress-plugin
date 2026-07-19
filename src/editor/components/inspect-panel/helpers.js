/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

// Always-present diagnostics appended to the copied payload only — never counted
// as removable post data. The data-key lists stay in PHP (single source of truth).
export const SYSTEM_META_KEYS = [
	'plugin_version',
	'wp_version',
	'wp_post_id',
];

/**
 * Does the post hold any BeyondWords data worth removing?
 *
 * Checks the data fields only (never the always-present system fields), so the
 * Remove button tracks the post's current state, not a mount-time snapshot.
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
 * Reads the same live `meta` as {@link hasBeyondwordsData} so Copy and Remove
 * can never disagree; the key lists render in the order supplied.
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
