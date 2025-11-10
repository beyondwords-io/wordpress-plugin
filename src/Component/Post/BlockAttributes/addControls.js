/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	TextControl,
	ToggleControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';

/**
 * Check if a block should have BeyondWords controls.
 *
 * @param {string} name Block name.
 * @return {boolean} Whether the block should have controls.
 */
function shouldHaveBeyondWordsControls( name ) {
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
 * Add BeyondWords controls to Gutenberg Blocks.
 *
 * @since 6.0.1 Skip internal/UI blocks to prevent breaking the block inserter.
 *
 * @param {Function} BlockEdit Block edit component.
 *
 * @return {Function} BlockEdit Modified block edit component.
 */
const withBeyondwordsBlockControls = createHigherOrderComponent(
	( BlockEdit ) => {
		return ( props ) => {
			const { name } = props;

			// Skip blocks that shouldn't have controls
			// Do this check BEFORE accessing attributes to avoid unnecessary processing
			if ( ! shouldHaveBeyondWordsControls( name ) ) {
				return <BlockEdit { ...props } />;
			}

			const { attributes, setAttributes } = props;
			const { beyondwordsAudio } = attributes;

			const icon = !! beyondwordsAudio
				? 'controls-volumeon'
				: 'controls-volumeoff';

			const buttonLabel = !! beyondwordsAudio
				? __( 'Disable audio processing', 'speechkit' )
				: __( 'Enable audio processing', 'speechkit' );

			const toggleLabel = !! beyondwordsAudio
				? __( 'Audio processing enabled', 'speechkit' )
				: __( 'Audio processing disabled', 'speechkit' );

			const toggleBeyondwordsAudio = () => {
				setAttributes( { beyondwordsAudio: ! beyondwordsAudio } );
			};

			return (
				<>
					<BlockEdit { ...props } />

					<InspectorControls>
						<PanelBody
							icon="controls-volumeon"
							title={ __( 'BeyondWords', 'speechkit' ) }
							initialOpen={ true }
						>
							<PanelRow>
								<ToggleControl
									label={ toggleLabel }
									checked={ !! beyondwordsAudio }
									onChange={ toggleBeyondwordsAudio }
									__nextHasNoMarginBottom
								/>
							</PanelRow>
						</PanelBody>
					</InspectorControls>

					<BlockControls>
						<ToolbarGroup>
							<ToolbarButton
								icon={ icon }
								label={ buttonLabel }
								className="components-toolbar__control"
								onClick={ toggleBeyondwordsAudio }
							/>
						</ToolbarGroup>
					</BlockControls>
				</>
			);
		};
	},
	'withBeyondwordsBlockControls'
);

addFilter(
	'editor.BlockEdit',
	'beyondwords/block-controls',
	withBeyondwordsBlockControls
);
