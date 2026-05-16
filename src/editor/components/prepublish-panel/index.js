/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow, ToggleControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';

const panel = ( { generateAudio, onGenerateAudioChange } ) => (
	<PanelBody title={ __( 'BeyondWords', 'speechkit' ) } initialOpen={ true }>
		<PanelRow>
			<ToggleControl
				label={ __( 'Generate audio', 'speechkit' ) }
				checked={ generateAudio }
				onChange={ ( value ) => {
					onGenerateAudioChange( value );
				} }
				__nextHasNoMarginBottom
			/>
		</PanelRow>
	</PanelBody>
);

export default compose(
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		const beyondwordsGenerateAudio =
			getEditedPostAttribute( 'meta' ).beyondwords_generate_audio;
		const speechkitGenerateAudio =
			getEditedPostAttribute( 'meta' ).speechkit_generate_audio;

		return {
			generateAudio:
				beyondwordsGenerateAudio === '1' ||
				speechkitGenerateAudio === '1',
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { editPost } = dispatch( 'core/editor' );

		return {
			onGenerateAudioChange: ( value ) => {
				editPost( {
					meta: {
						beyondwords_generate_audio: value ? '1' : '0',
					},
				} );
			},
		};
	} )
)( panel );
