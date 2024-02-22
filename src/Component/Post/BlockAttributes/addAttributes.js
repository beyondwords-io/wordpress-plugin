/**
 * WordPress Dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * External dependencies
 */
import getBlockMarkerAttribute from './helpers/getBlockMarkerAttribute';

/**
 * Register custom block attributes for BeyondWords.
 *
 * @since 4.0.4 Remove settings.attributes undefined check, to match official docs.
 *
 * @param {Object} settings Settings for the block.
 *
 * @return {Object} settings Modified settings.
 */
function addAttributes( settings ) {
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

/**
 * Set a unique BeyondWords marker for each block that doesn't already have one.
 *
 * @param {Object} attributes Attributes for the block.
 *
 * @return {Object} attributes Modified attributes.
 */
function setMarkerAttribute( attributes ) {
	const marker = getBlockMarkerAttribute( attributes );

	attributes = {
		...attributes,
		beyondwordsMarker: marker,
	};

	return attributes;
}

addFilter(
	'blocks.getBlockAttributes',
	'beyondwords/set-marker-attribute',
	setMarkerAttribute
);
