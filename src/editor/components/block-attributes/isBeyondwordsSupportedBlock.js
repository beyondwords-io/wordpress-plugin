/**
 * Check if a block is supported by BeyondWords.
 * Only content blocks that can be read aloud should have BeyondWords attributes and controls.
 *
 * @param {string} name Block name.
 * @return {boolean} Whether the block is supported by BeyondWords.
 */
export function isBeyondwordsSupportedBlock( name ) {
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
