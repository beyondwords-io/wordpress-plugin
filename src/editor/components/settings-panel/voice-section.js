/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { MODEL_OPTIONS } from './helpers';

export function VoiceSection() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const settings = useSelect(
		( select ) => select( 'beyondwords/settings' ).getSettings(),
		[]
	);

	const languages = useSelect(
		( select ) => select( 'beyondwords/settings' ).getLanguages(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const languageCode =
		meta.beyondwords_language_code || settings.projectLanguageCode || '';
	const voiceId = meta.beyondwords_body_voice_id || '';
	const voiceModelId =
		meta.beyondwords_voice_model_id || MODEL_OPTIONS[ 0 ].value;

	const voices = useSelect(
		( select ) =>
			languageCode
				? select( 'beyondwords/settings' ).getVoices( languageCode )
				: [],
		[ languageCode ]
	);

	const setLanguageCode = ( value ) => {
		setMeta( { ...meta, beyondwords_language_code: value } );
	};

	const setVoiceId = ( value ) => {
		setMeta( { ...meta, beyondwords_body_voice_id: value } );
	};

	const setVoiceModelId = ( value ) => {
		setMeta( { ...meta, beyondwords_voice_model_id: value } );
	};

	const languageOptions = ( languages ?? [] ).map( ( language ) => ( {
		label: `${ decodeEntities( language.name ) } (${ decodeEntities(
			language.accent
		) })`,
		value: decodeEntities( language.code ),
	} ) );

	const voiceOptions = ( voices ?? [] ).map( ( voice ) => ( {
		label: decodeEntities( voice.name ),
		value: String( voice.id ),
	} ) );

	return (
		<PanelBody title={ __( 'Voice', 'speechkit' ) } initialOpen={ true }>
			{ languageOptions.length > 0 && (
				<SelectControl
					className="beyondwords--language"
					label={ __( 'Language', 'speechkit' ) }
					options={ languageOptions }
					value={ languageCode }
					onChange={ setLanguageCode }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
			{ voiceOptions.length > 0 && (
				<SelectControl
					className="beyondwords--voice"
					label={ __( 'Voice', 'speechkit' ) }
					options={ voiceOptions }
					value={ voiceId }
					onChange={ setVoiceId }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
			<SelectControl
				className="beyondwords--model"
				label={ __( 'Model', 'speechkit' ) }
				options={ MODEL_OPTIONS }
				value={ voiceModelId }
				onChange={ setVoiceModelId }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
		</PanelBody>
	);
}

export default VoiceSection;
