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
import { useEffect } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';

/**
 * External dependencies
 */
import getBlockMarkerAttribute from './helpers/getBlockMarkerAttribute';

/**
 * Internal dependencies
 */
import BlockAttributesCheck from './check';

/**
 * Add BeyondWords controls to Gutenberg Blocks.
 *
 * @param {Function} BlockEdit Block edit component.
 *
 * @return {Function} BlockEdit Modified block edit component.
 */
const withBeyondwordsBlockControls = createHigherOrderComponent(
	( BlockEdit ) => {
		return ( props ) => {
			const { attributes, setAttributes } = props;

			useEffect( () => {
				setAttributes( {
					beyondwordsMarker: getBlockMarkerAttribute( attributes )
				} );
			}, [] );

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

			const toggleBeyondwordsAudio = () =>
				setAttributes( { beyondwordsAudio: ! beyondwordsAudio } );

			return (
				<>
					<BlockEdit { ...props } />

					<BlockAttributesCheck>
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
					</BlockAttributesCheck>
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
