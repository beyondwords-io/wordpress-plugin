/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import { PanelBody, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { isBeyondwordsSupportedBlock } from './isBeyondwordsSupportedBlock';
import Stack from '../stack';
import Toggle from '../toggle';
import VoicePicker from '../voice-picker';
import { BeyondwordsTitle } from '../icon';

/**
 * Add BeyondWords controls to Gutenberg Blocks.
 *
 * @since 6.0.1 Skip internal/UI blocks to prevent breaking the block inserter.
 * @since 7.1.0 Add the per-block Language and Voice pickers.
 *
 * @param {Function} BlockEdit Block edit component.
 *
 * @return {Function} BlockEdit Modified block edit component.
 */
const withBeyondwordsBlockControls = createHigherOrderComponent(
	( BlockEdit ) => {
		// A component of its own, so the unsupported-block check can return
		// before any hook runs.
		const BeyondwordsControls = ( props ) => {
			const { attributes, setAttributes } = props;
			const {
				beyondwordsAudio,
				beyondwordsLanguageCode,
				beyondwordsVoiceId,
			} = attributes;

			// "Customize" is opt-in: a block counts as customised once it carries
			// an explicit language or voice.
			const [ customize, setCustomize ] = useState(
				() => !! ( beyondwordsLanguageCode || beyondwordsVoiceId )
			);

			// What this block already reads aloud with: the post's own choice
			// where it has one, otherwise the project's. Fetched only once
			// Customize is on, so an untouched block still costs no requests.
			const seed = useSelect(
				( select ) => {
					const meta =
						select( 'core/editor' ).getEditedPostAttribute(
							'meta'
						) || {};

					if ( meta.beyondwords_language_code ) {
						return {
							languageCode: meta.beyondwords_language_code,
							voiceId: meta.beyondwords_body_voice_id || '',
						};
					}

					const projectId =
						meta.beyondwords_project_id ||
						select( 'beyondwords/settings' ).getSettings()
							?.projectId;

					const project =
						customize && projectId
							? select( 'beyondwords/settings' ).getProject(
									projectId
							  )
							: null;

					return {
						languageCode: project?.language || '',
						voiceId: String( project?.body?.voice?.id ?? '' ),
					};
				},
				[ customize ]
			);

			const icon = beyondwordsAudio
				? 'controls-volumeon'
				: 'controls-volumeoff';

			const buttonLabel = beyondwordsAudio
				? __( 'Disable generation', 'speechkit' )
				: __( 'Enable generation', 'speechkit' );

			const toggleLabel = beyondwordsAudio
				? __( 'Generation enabled', 'speechkit' )
				: __( 'Generation disabled', 'speechkit' );

			const toggleBeyondwordsAudio = () => {
				setAttributes( { beyondwordsAudio: ! beyondwordsAudio } );
			};

			const setVoice = ( { languageCode, voiceId } ) => {
				setAttributes( {
					beyondwordsLanguageCode: languageCode,
					beyondwordsVoiceId: voiceId,
				} );
			};

			// Customize off returns the block to the post's language and voice.
			const toggleCustomize = () => {
				const next = ! customize;
				setCustomize( next );

				if ( ! next ) {
					setVoice( { languageCode: '', voiceId: '' } );
				}
			};

			return (
				<>
					<BlockEdit { ...props } />

					<InspectorControls>
						<PanelBody
							className="beyondwords--block-settings"
							title={ <BeyondwordsTitle /> }
							initialOpen={ true }
						>
							<Stack>
								<Toggle
									label={ toggleLabel }
									checked={ !! beyondwordsAudio }
									onChange={ toggleBeyondwordsAudio }
								/>
								{ !! beyondwordsAudio && (
									<Toggle
										className="beyondwords--customize-block"
										label={ __( 'Customize', 'speechkit' ) }
										checked={ customize }
										onChange={ toggleCustomize }
									/>
								) }
								{ !! beyondwordsAudio && (
									<VoicePicker
										enabled={ customize }
										seed={ seed }
										languageCode={
											beyondwordsLanguageCode || ''
										}
										voiceId={ beyondwordsVoiceId || '' }
										onChange={ setVoice }
									/>
								) }
							</Stack>
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

		return ( props ) => {
			if ( ! isBeyondwordsSupportedBlock( props.name ) ) {
				return <BlockEdit { ...props } />;
			}

			return <BeyondwordsControls { ...props } />;
		};
	},
	'withBeyondwordsBlockControls'
);

addFilter(
	'editor.BlockEdit',
	'beyondwords/block-controls',
	withBeyondwordsBlockControls
);
