/**
 * WordPress Dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { isBeyondwordsSupportedBlock } from './isBeyondwordsSupportedBlock';

/**
 * Register custom block attributes for BeyondWords.
 *
 * @since 4.0.4 Remove settings.attributes undefined check, to match official docs.
 * @since 6.0.1 Skip internal/UI blocks to prevent breaking the block inserter.
 * @since 7.0.0 Add the per-block language and voice attributes.
 *
 * @param {Object} settings Settings for the block.
 * @param {string} name     Block name.
 *
 * @return {Object} settings Modified settings.
 */
function addAttributes( settings, name ) {
	if ( ! isBeyondwordsSupportedBlock( name ) ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			beyondwordsAudio: {
				type: 'boolean',
				default: true,
			},
			beyondwordsMarker: {
				type: 'string',
				default: '',
			},
			// Empty values are never serialized, so a block without overrides
			// keeps the markup it already has.
			beyondwordsLanguageCode: {
				type: 'string',
				default: '',
			},
			beyondwordsVoiceId: {
				type: 'string',
				default: '',
			},
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'beyondwords/beyondwords-block-attributes',
	addAttributes
);
