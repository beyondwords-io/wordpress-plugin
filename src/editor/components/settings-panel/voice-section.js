/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { select, useSelect } from '@wordpress/data';
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
} from './helpers';
import Stack from '../stack';
import Toggle from '../toggle';

export function VoiceSection( { withPanel = true } ) {
	const postType = useSelect(
		( s ) => s( 'core/editor' ).getCurrentPostType(),
		[]
	);

	// `useEntityProp` yields undefined meta until the post entity record is hydrated.
	const [ rawMeta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const meta = rawMeta ?? {};

	const languageCode = meta.beyondwords_language_code || '';
	const voiceId = meta.beyondwords_body_voice_id || '';

	// "Customize" is opt-in: a post counts as customised once it carries an explicit
	// language or voice. Local state so toggling on reveals the pickers pre-choice.
	const [ customize, setCustomize ] = useState(
		() => !! ( languageCode || voiceId )
	);

	const projectId = useSelect(
		( s ) =>
			s( 'core/editor' ).getEditedPostAttribute( 'meta' )
				?.beyondwords_project_id ||
			s( 'beyondwords/settings' ).getSettings()?.projectId,
		[]
	);

	// Customize-on with no choice yet: fetch the project default language and seed
	// only the Language (a project default voice can belong to another language).
	const needsDefault = customize && ! languageCode && ! voiceId;

	const project = useSelect(
		( s ) =>
			needsDefault
				? s( 'beyondwords/settings' ).getProject( projectId )
				: null,
		[ needsDefault, projectId ]
	);

	// True once the project fetch has settled; keeps the spinner up until then to
	// avoid a one-frame "empty dropdown then spinner" flicker.
	const projectResolved = useSelect(
		( s ) =>
			! needsDefault ||
			s( 'beyondwords/settings' ).hasFinishedResolution( 'getProject', [
				projectId,
			] ),
		[ needsDefault, projectId ]
	);

	// Apply the resolved default language. Reads the freshest meta — not the
	// closure's — so a concurrent edit during the async fetch isn't clobbered.
	useEffect( () => {
		if ( needsDefault && project?.language ) {
			const current =
				select( 'core/editor' ).getEditedPostAttribute( 'meta' );
			setMeta( {
				...current,
				beyondwords_language_code: project.language,
			} );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ needsDefault, project ] );

	const loadingProject = customize && needsDefault && ! projectResolved;

	// Fetched lazily behind the toggle — a default post makes no language/voice API calls.
	const languages = useSelect(
		( s ) =>
			customize ? s( 'beyondwords/settings' ).getLanguages() : [],
		[ customize ]
	);

	const voices = useSelect(
		( s ) =>
			customize && languageCode
				? s( 'beyondwords/settings' ).getVoices( languageCode )
				: [],
		[ customize, languageCode ]
	);

	// `hasFinishedResolution` is monotonic; `isResolving` flip-flops and leaves a
	// one-frame gap where stale voices show.
	const languagesResolving = useSelect(
		( s ) =>
			customize &&
			! s( 'beyondwords/settings' ).hasFinishedResolution(
				'getLanguages',
				[]
			),
		[ customize ]
	);

	const voicesResolving = useSelect(
		( s ) =>
			customize &&
			!! languageCode &&
			! s( 'beyondwords/settings' ).hasFinishedResolution( 'getVoices', [
				languageCode,
			] ),
		[ customize, languageCode ]
	);

	const [ nativeFilter, setNativeFilter ] = useState( NATIVE_ONLY );

	// Open on "All" when the saved voice is not native to the language, so that
	// voice stays visible in the list.
	const nativeSeeded = useRef( false );
	useEffect( () => {
		if ( nativeSeeded.current || ! customize || voicesResolving ) {
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
	}, [ customize, voicesResolving, voices, voiceId, languageCode ] );

	const setVoiceId = ( value ) => {
		setMeta( { ...meta, beyondwords_body_voice_id: value } );
	};

	// Seeds the default body voice too: we never send the language itself, so a
	// concrete voice must always be stored.
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

	const setLanguageName = ( name ) => {
		const first = getAccentsForName( languages, name )[ 0 ];
		setLanguageCode( first ? first.value : '' );
	};

	// Customize off reverts to the project defaults by clearing both choices.
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

	// The Model + Voice group is hidden with an inline style rather than
	// unmounted, so the <select> can't detach mid-interaction or lose to
	// component CSS specificity.
	const fieldsReady = customize && ! loadingProject && ! languagesResolving;

	const fields = (
		<Stack>
			<Toggle
				className="beyondwords--customize"
				label={ __( 'Customize', 'speechkit' ) }
				checked={ customize }
				onChange={ toggleCustomize }
			/>
			{ customize && ( loadingProject || languagesResolving ) && (
				<div className="beyondwords--languages-spinner">
					<Spinner />
				</div>
			) }
			{ fieldsReady && (
				<SelectControl
					className="beyondwords--language"
					label={ __( 'Language', 'speechkit' ) }
					options={ languageNameOptions }
					value={ languageName }
					onChange={ setLanguageName }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
			{ fieldsReady && showAccent && (
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
			{ fieldsReady && languageCode && (
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
			{ fieldsReady && languageCode && ( showModel || showVoice ) && (
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
			{ fieldsReady && languageCode && voicesResolving && (
				<div className="beyondwords--voice-spinner">
					<Spinner />
				</div>
			) }
		</Stack>
	);

	// Document/pre-publish panels render the fields without nesting another panel.
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
