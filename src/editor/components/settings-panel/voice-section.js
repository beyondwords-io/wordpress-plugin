/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { getVoiceModelVariants, voiceModelLabel } from './helpers';
import Stack from '../stack';
import Toggle from '../toggle';

export function VoiceSection( { withPanel = true } ) {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const languageCode = meta.beyondwords_language_code || '';
	const voiceId = meta.beyondwords_body_voice_id || '';

	// "Customize" is opt-in. A post counts as customised as soon as it carries an
	// explicit language or voice; otherwise it uses the project defaults, we show
	// no fields and send nothing. Held in local state so toggling on can reveal
	// the pickers before any choice has been made.
	const [ customize, setCustomize ] = useState(
		() => !! ( languageCode || voiceId )
	);

	// Languages and voices are only needed while customising, so they are fetched
	// lazily behind the toggle — a default post makes no language/voice API calls.
	const languages = useSelect(
		( select ) =>
			customize ? select( 'beyondwords/settings' ).getLanguages() : [],
		[ customize ]
	);

	const voices = useSelect(
		( select ) =>
			customize && languageCode
				? select( 'beyondwords/settings' ).getVoices( languageCode )
				: [],
		[ customize, languageCode ]
	);

	// Changing the Language re-fetches its voices; show a Spinner in place of the
	// Voice/Model dropdowns until that resolves.
	const isResolvingVoices = useSelect(
		( select ) =>
			customize &&
			!! languageCode &&
			select( 'beyondwords/settings' ).isResolving( 'getVoices', [
				languageCode,
			] ),
		[ customize, languageCode ]
	);

	const setVoiceId = ( value ) => {
		setMeta( { ...meta, beyondwords_body_voice_id: value } );
	};

	// Picking a Language sets the language *and* seeds the voice with that
	// language's default body voice, so a concrete voice is always stored (we
	// never send the language itself — the voice carries it). Languages with no
	// default voice fall back to the "Select a voice" placeholder.
	const setLanguageCode = ( value ) => {
		const language = ( languages ?? [] ).find(
			( item ) => decodeEntities( item.code ) === value
		);
		const defaultVoiceId = language?.default_voices?.body?.id;

		setMeta( {
			...meta,
			beyondwords_language_code: value,
			beyondwords_body_voice_id: defaultVoiceId
				? String( defaultVoiceId )
				: '',
		} );
	};

	// Toggling Customize off reverts the post to the project defaults by clearing
	// both choices; toggling on just reveals the (empty) pickers.
	const toggleCustomize = () => {
		const next = ! customize;
		setCustomize( next );

		if ( ! next ) {
			setMeta( {
				...meta,
				beyondwords_language_code: '',
				beyondwords_body_voice_id: '',
			} );
		}
	};

	const languageOptions = [
		{ label: __( 'Select a language…', 'speechkit' ), value: '' },
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
		{ label: __( 'Select a voice', 'speechkit' ), value: '' },
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

	const hasVoices = ( voices ?? [] ).length > 0;

	const fields = (
		<Stack>
			<Toggle
				className="beyondwords--customize"
				label={ __( 'Customize', 'speechkit' ) }
				checked={ customize }
				onChange={ toggleCustomize }
			/>
			{ customize && (
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
			{ customize && isResolvingVoices && <Spinner /> }
			{ customize && hasVoices && (
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
			{ customize && showModel && (
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

	// In the document/pre-publish panels we render the fields directly inside the
	// existing "BeyondWords" panel rather than nesting another panel.
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
