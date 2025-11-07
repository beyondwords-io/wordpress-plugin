/**
 * WordPress Dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Check if a block should have BeyondWords attributes.
 * Only content blocks that can be read aloud should have these attributes.
 *
 * @param {string} name Block name.
 * @return {boolean} Whether the block should have BeyondWords attributes.
 */
function shouldHaveBeyondWordsAttributes( name ) {
	// Skip blocks without a name
	if ( ! name ) {
		return false;
	}

	// Skip internal/UI blocks
	if ( name.startsWith( '__' ) ) {
		return false;
	}

	// Skip reusable blocks and template parts (these are containers)
	if (
		name.startsWith( 'core/block' ) ||
		name.startsWith( 'core/template' )
	) {
		return false;
	}

	// Skip editor UI blocks
	const excludedBlocks = [
		'core/freeform', // Classic editor
		'core/legacy-widget',
		'core/widget-area',
		'core/navigation',
		'core/navigation-link',
		'core/navigation-submenu',
		'core/site-logo',
		'core/site-title',
		'core/site-tagline',
	];

	if ( excludedBlocks.includes( name ) ) {
		return false;
	}

	return true;
}

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
	if ( ! shouldHaveBeyondWordsAttributes( name ) ) {
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
