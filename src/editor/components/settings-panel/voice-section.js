/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import {
	projectDefaultOption,
	getVoiceModelVariants,
	voiceModelLabel,
} from './helpers';
import Stack from '../stack';

export function VoiceSection( { withPanel = true } ) {
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

	const voices = useSelect(
		( select ) =>
			languageCode
				? select( 'beyondwords/settings' ).getVoices( languageCode )
				: [],
		[ languageCode ]
	);

	// Changing the Language re-fetches its voices; show a Spinner in place of
	// the Voice/Model dropdowns until that resolves.
	const isResolvingVoices = useSelect(
		( select ) =>
			!! languageCode &&
			select( 'beyondwords/settings' ).isResolving( 'getVoices', [
				languageCode,
			] ),
		[ languageCode ]
	);

	const setLanguageCode = ( value ) => {
		setMeta( { ...meta, beyondwords_language_code: value } );
	};

	const setVoiceId = ( value ) => {
		setMeta( { ...meta, beyondwords_body_voice_id: value } );
	};

	const hasLanguages = ( languages ?? [] ).length > 0;
	const hasVoices = ( voices ?? [] ).length > 0;

	const languageOptions = [
		projectDefaultOption(),
		...( languages ?? [] ).map( ( language ) => ( {
			label: `${ decodeEntities( language.name ) } (${ decodeEntities(
				language.accent
			) })`,
			value: decodeEntities( language.code ),
		} ) ),
	];

	// Voice dropdown lists distinct voice names. ElevenLabs voices repeat a name
	// once per model, so deduping by name keeps the list clean; the Model
	// dropdown then selects the actual variant (voice id) within a name.
	const voicesByName = {};
	const voiceNames = [];
	( voices ?? [] ).forEach( ( voice ) => {
		if ( ! voicesByName[ voice.name ] ) {
			voicesByName[ voice.name ] = [];
			voiceNames.push( voice.name );
		}
		voicesByName[ voice.name ].push( voice );
	} );

	const selectedVoice = ( voices ?? [] ).find(
		( voice ) => String( voice.id ) === String( voiceId )
	);
	const selectedVoiceName = selectedVoice?.name || '';

	const voiceOptions = [
		projectDefaultOption(),
		...voiceNames.map( ( name ) => ( {
			label: decodeEntities( name ),
			value: name,
		} ) ),
	];

	// Picking a name selects that name's default model variant.
	const setVoiceName = ( name ) => {
		if ( ! name ) {
			setVoiceId( '' );
			return;
		}
		const variants = getVoiceModelVariants(
			voicesByName[ name ][ 0 ],
			voices
		);
		const defaultVariant = variants[ 0 ] ?? voicesByName[ name ][ 0 ];
		setVoiceId( String( defaultVariant.id ) );
	};

	const modelVariants = selectedVoice
		? getVoiceModelVariants( selectedVoice, voices )
		: [];
	const showModel = modelVariants.length > 1;

	const modelOptions = modelVariants.map( ( variant ) => ( {
		label: voiceModelLabel( variant.model_id ),
		value: String( variant.id ),
	} ) );

	const fields = (
		<Stack>
			{ hasLanguages && (
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
			{ isResolvingVoices && <Spinner /> }
			{ hasVoices && (
				<SelectControl
					className="beyondwords--voice"
					label={ __( 'Voice', 'speechkit' ) }
					options={ voiceOptions }
					value={ selectedVoiceName }
					onChange={ setVoiceName }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
			{ showModel && (
				<SelectControl
					className="beyondwords--model"
					label={ __( 'Model', 'speechkit' ) }
					options={ modelOptions }
					value={ String( voiceId ) }
					onChange={ setVoiceId }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
		</Stack>
	);

	// In the document/pre-publish panels we render the fields directly inside
	// the existing "BeyondWords" panel rather than nesting another panel.
	if ( ! withPanel ) {
		return fields;
	}

	return (
		<PanelBody title={ __( 'Voice', 'speechkit' ) } initialOpen={ true }>
			{ fields }
		</PanelBody>
	);
}

export default VoiceSection;
