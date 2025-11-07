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
 * External dependencies
 */
import { v4 as uuidv4 } from 'uuid';

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
			const { beyondwordsAudio, beyondwordsMarker } = attributes;

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
				const newAudioValue = ! beyondwordsAudio;
				const updates = { beyondwordsAudio: newAudioValue };

				// Only set marker when enabling audio and marker doesn't exist
				if ( newAudioValue && ! beyondwordsMarker ) {
					updates.beyondwordsMarker = uuidv4();
				}

				setAttributes( updates );
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
							{ !! beyondwordsAudio && (
								<PanelRow>
									{ beyondwordsMarker ? (
										<TextControl
											label={ __(
												'Segment marker',
												'speechkit'
											) }
											value={ beyondwordsMarker }
											disabled
											readOnly
											__nextHasNoMarginBottom
										/>
									) : (
										<div style={ { width: '100%' } }>
											<div
												style={ {
													display: 'block',
													marginBottom: '8px',
													fontSize: '11px',
													fontWeight: 500,
													lineHeight: '1.4',
													textTransform: 'uppercase',
													color: '#1e1e1e',
												} }
											>
												{ __(
													'Segment marker',
													'speechkit'
												) }
											</div>
											<div
												style={ {
													padding: '6px 8px',
													border: '1px solid #949494',
													borderRadius: '2px',
													backgroundColor: '#f0f0f0',
													color: '#757575',
													fontSize: '13px',
													fontStyle: 'italic',
												} }
											>
												{ __(
													'Generated on save',
													'speechkit'
												) }
											</div>
										</div>
									) }
								</PanelRow>
							) }
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
