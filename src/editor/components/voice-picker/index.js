/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import {
	NATIVE_ALL,
	NATIVE_ONLY,
	filterVoicesByNative,
	findLanguageByCode,
	getAccentsForName,
	getLanguageModels,
	getLanguageNames,
	voiceIsNative,
	voiceModelKey,
} from '../settings-panel/helpers';
import Stack from '../stack';

/**
 * The Language → Accent → Native → Model → Voice pickers.
 *
 * Shared by the post sidebar and the block inspector; the caller owns where the
 * pair is stored and when it is fetched.
 *
 * @param {Object}   props
 * @param {boolean}  props.enabled      Whether to fetch and show the fields.
 * @param {string}   props.languageCode The selected language code.
 * @param {string}   props.voiceId      The selected voice id.
 * @param {Object}   [props.seed]       A { languageCode, voiceId } pair to start from.
 * @param {Function} props.onChange     Passed the next { languageCode, voiceId } pair.
 *
 * @return {Element|null} The pickers.
 */
export function VoicePicker( {
	enabled = true,
	languageCode = '',
	voiceId = '',
	seed,
	onChange,
} ) {
	// Fetched lazily behind `enabled` — a default post makes no language/voice API calls.
	const languages = useSelect(
		( select ) =>
			enabled ? select( 'beyondwords/settings' ).getLanguages() : [],
		[ enabled ]
	);

	const voices = useSelect(
		( select ) =>
			enabled && languageCode
				? select( 'beyondwords/settings' ).getVoices( languageCode )
				: [],
		[ enabled, languageCode ]
	);

	// `hasFinishedResolution` is monotonic; `isResolving` flip-flops and leaves a
	// one-frame gap where stale voices show.
	const languagesResolving = useSelect(
		( select ) =>
			enabled &&
			! select( 'beyondwords/settings' ).hasFinishedResolution(
				'getLanguages',
				[]
			),
		[ enabled ]
	);

	const voicesResolving = useSelect(
		( select ) =>
			enabled &&
			!! languageCode &&
			! select( 'beyondwords/settings' ).hasFinishedResolution(
				'getVoices',
				[ languageCode ]
			),
		[ enabled, languageCode ]
	);

	// Start from what the caller says this already inherits, so picking a
	// different voice in the same language costs one click, not four.
	//
	// Two steps, because the voice can only be trusted once the language's own
	// list is back to confirm it offers it: 0 = untouched, 1 = language in,
	// 2 = settled.
	const seedStep = useRef( 0 );

	useEffect( () => {
		if ( ! enabled || seedStep.current !== 0 ) {
			return;
		}

		// Anything already chosen wins over the seed.
		if ( languageCode || voiceId ) {
			seedStep.current = 2;
			return;
		}

		if ( ! seed?.languageCode ) {
			return;
		}

		seedStep.current = 1;
		onChange( { languageCode: seed.languageCode, voiceId: '' } );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ enabled, seed?.languageCode, languageCode, voiceId ] );

	useEffect( () => {
		if ( seedStep.current !== 1 || voicesResolving ) {
			return;
		}

		if ( ! seed?.voiceId || languageCode !== seed.languageCode ) {
			return;
		}

		seedStep.current = 2;

		// A project's default voice need not speak the project's language.
		const offered = ( voices ?? [] ).some(
			( voice ) => String( voice.id ) === String( seed.voiceId )
		);

		if ( offered ) {
			onChange( { languageCode, voiceId: String( seed.voiceId ) } );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ seed, languageCode, voices, voicesResolving ] );

	const [ nativeFilter, setNativeFilter ] = useState( NATIVE_ONLY );

	// Open on "All" when the saved voice is not native to the language, so that
	// voice stays visible in the list.
	const nativeSeeded = useRef( false );
	useEffect( () => {
		if ( nativeSeeded.current || ! enabled || voicesResolving ) {
			return;
		}
		const saved = ( voices ?? [] ).find(
			( voice ) => String( voice.id ) === String( voiceId )
		);
		if ( voiceId && ! saved ) {
			return;
		}
		nativeSeeded.current = true;
		if ( saved && languageCode && ! voiceIsNative( saved, languageCode ) ) {
			setNativeFilter( NATIVE_ALL );
		}
	}, [ enabled, voicesResolving, voices, voiceId, languageCode ] );

	const setVoiceId = ( value ) => {
		onChange( { languageCode, voiceId: value } );
	};

	// Seeds the language's default body voice, so a stored language always
	// carries a voice that can speak it.
	const setLanguageCode = ( value ) => {
		const language = ( languages ?? [] ).find(
			( item ) => decodeEntities( item.code ) === value
		);
		const defaultVoiceId = language?.default_voices?.body?.id;

		onChange( {
			languageCode: value,
			voiceId: defaultVoiceId ? String( defaultVoiceId ) : '',
		} );
	};

	const setLanguageName = ( name ) => {
		const first = getAccentsForName( languages, name )[ 0 ];
		setLanguageCode( first ? first.value : '' );
	};

	// The Accent select carries the language CODE — it is the stored value, and
	// a (name, accent) pair maps to exactly one code.
	const selectedLanguage = findLanguageByCode( languages, languageCode );
	const languageName = selectedLanguage
		? decodeEntities( selectedLanguage.name )
		: '';

	const languageNameOptions = [
		{ label: __( 'Select a language…', 'speechkit' ), value: '' },
		...getLanguageNames( languages ).map( ( name ) => ( {
			label: name,
			value: name,
		} ) ),
	];

	const accentOptions = getAccentsForName( languages, languageName );

	const showAccent = accentOptions.length > 1;

	const filteredVoices = filterVoicesByNative(
		voices,
		languageCode,
		nativeFilter,
		voiceId
	);

	// "Model" is a language-level filter over the voices; with a single bucket
	// there is no Model dropdown and every voice is listed.
	const models = getLanguageModels( filteredVoices );
	const showModel = models.length > 1;

	const selectedVoice = filteredVoices.find(
		( voice ) => String( voice.id ) === String( voiceId )
	);
	// Derived from the selected voice — we persist only the voice id.
	const selectedModelKey = selectedVoice
		? voiceModelKey( selectedVoice )
		: '';

	const bucketVoices = showModel
		? filteredVoices.filter(
				( voice ) => voiceModelKey( voice ) === selectedModelKey
		  )
		: filteredVoices;

	const hasVoices = filteredVoices.length > 0;

	// Model gates the Voice list: hide Voice until a model is chosen.
	const showVoice = hasVoices && ( ! showModel || '' !== selectedModelKey );

	const modelOptions = [
		{ label: __( 'Select a model', 'speechkit' ), value: '' },
		...models.map( ( model ) => ( {
			label: decodeEntities( model.label ),
			value: model.key,
		} ) ),
	];

	const voiceOptions = [
		{ label: __( 'Select a voice', 'speechkit' ), value: '' },
		...bucketVoices.map( ( voice ) => ( {
			label: decodeEntities( voice.name ),
			value: String( voice.id ),
		} ) ),
	];

	// Picking a Model selects that bucket's first voice, so a concrete voice is
	// always stored (the voice carries the model).
	const setModel = ( key ) => {
		const first = filteredVoices.find(
			( voice ) => voiceModelKey( voice ) === key
		);
		setVoiceId( first ? String( first.id ) : '' );
	};

	if ( ! enabled ) {
		return null;
	}

	if ( languagesResolving ) {
		return (
			<div className="beyondwords--languages-spinner">
				<Spinner />
			</div>
		);
	}

	return (
		<Stack>
			<SelectControl
				className="beyondwords--language"
				label={ __( 'Language', 'speechkit' ) }
				options={ languageNameOptions }
				value={ languageName }
				onChange={ setLanguageName }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			{ showAccent && (
				<SelectControl
					className="beyondwords--accent"
					label={ __( 'Accent', 'speechkit' ) }
					options={ accentOptions }
					value={ languageCode }
					onChange={ setLanguageCode }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
			{ languageCode && (
				<SelectControl
					className="beyondwords--native"
					label={ __( 'Native', 'speechkit' ) }
					options={ [
						{
							label: __( 'Native', 'speechkit' ),
							value: NATIVE_ONLY,
						},
						{ label: __( 'All', 'speechkit' ), value: NATIVE_ALL },
					] }
					value={ nativeFilter }
					onChange={ setNativeFilter }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
			{ languageCode && ( showModel || showVoice ) && (
				// Hidden with an inline style rather than unmounted, so the
				// <select> can't detach mid-interaction or lose to component CSS.
				<div
					className="beyondwords--voice-fields"
					style={ voicesResolving ? { display: 'none' } : undefined }
				>
					<Stack>
						{ showModel && (
							<SelectControl
								className="beyondwords--model"
								label={ __( 'Model', 'speechkit' ) }
								options={ modelOptions }
								value={ selectedModelKey }
								onChange={ setModel }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						) }
						{ showVoice && (
							<SelectControl
								className="beyondwords--voice"
								label={ __( 'Voice', 'speechkit' ) }
								options={ voiceOptions }
								value={ String( voiceId ) }
								onChange={ setVoiceId }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						) }
					</Stack>
				</div>
			) }
			{ languageCode && voicesResolving && (
				<div className="beyondwords--voice-spinner">
					<Spinner />
				</div>
			) }
		</Stack>
	);
}

export default VoicePicker;
