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
 *
 * @param {Object} settings Settings for the block.
 * @param {string} name     Block name.
 *
 * @return {Object} settings Modified settings.
 */
function addAttributes( settings, name ) {
	// Only add attributes to content blocks
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
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'beyondwords/beyondwords-block-attributes',
	addAttributes
);
