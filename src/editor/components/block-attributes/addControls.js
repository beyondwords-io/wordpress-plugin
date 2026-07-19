/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { isBeyondwordsSupportedBlock } from './isBeyondwordsSupportedBlock';
import Toggle from '../toggle';
import { BeyondwordsTitle } from '../icon';

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

			// Check BEFORE accessing attributes to avoid unnecessary processing.
			if ( ! isBeyondwordsSupportedBlock( name ) ) {
				return <BlockEdit { ...props } />;
			}

			const { attributes, setAttributes } = props;
			const { beyondwordsAudio } = attributes;

			const icon = !! beyondwordsAudio
				? 'controls-volumeon'
				: 'controls-volumeoff';

			const buttonLabel = !! beyondwordsAudio
				? __( 'Disable generation', 'speechkit' )
				: __( 'Enable generation', 'speechkit' );

			const toggleLabel = !! beyondwordsAudio
				? __( 'Generation enabled', 'speechkit' )
				: __( 'Generation disabled', 'speechkit' );

			const toggleBeyondwordsAudio = () => {
				setAttributes( { beyondwordsAudio: ! beyondwordsAudio } );
			};

			return (
				<>
					<BlockEdit { ...props } />

					<InspectorControls>
						<PanelBody
							title={ <BeyondwordsTitle /> }
							initialOpen={ true }
						>
							<PanelRow>
								<Toggle
									label={ toggleLabel }
									checked={ !! beyondwordsAudio }
									onChange={ toggleBeyondwordsAudio }
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
