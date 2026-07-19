/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { select, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { getLanguageModels, voiceModelKey } from './helpers';
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

	// Show a Spinner in place of the Voice/Model dropdowns while voices re-fetch.
	const isResolvingVoices = useSelect(
		( s ) =>
			customize &&
			!! languageCode &&
			s( 'beyondwords/settings' ).isResolving( 'getVoices', [
				languageCode,
			] ),
		[ customize, languageCode ]
	);

	const setVoiceId = ( value ) => {
		setMeta( { ...meta, beyondwords_body_voice_id: value } );
	};

	// Picking a Language also seeds its default body voice so a concrete voice is
	// always stored (we never send the language itself — the voice carries it).
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

	const languageOptions = [
		{ label: __( 'Select a language…', 'speechkit' ), value: '' },
		...( languages ?? [] ).map( ( language ) => ( {
			label: `${ decodeEntities( language.name ) } (${ decodeEntities(
				language.accent
			) })`,
			value: decodeEntities( language.code ),
		} ) ),
	];

	// "Model" is a language-level filter over the voices; with a single bucket
	// there is no Model dropdown and every voice is listed.
	const models = getLanguageModels( voices );
	const showModel = models.length > 1;

	const selectedVoice = ( voices ?? [] ).find(
		( voice ) => String( voice.id ) === String( voiceId )
	);
	// Derived from the selected voice — we persist only the voice id.
	const selectedModelKey = selectedVoice
		? voiceModelKey( selectedVoice )
		: '';

	const bucketVoices = showModel
		? ( voices ?? [] ).filter(
				( voice ) => voiceModelKey( voice ) === selectedModelKey
		  )
		: voices ?? [];

	const hasVoices = ( voices ?? [] ).length > 0;

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
		const first = ( voices ?? [] ).find(
			( voice ) => voiceModelKey( voice ) === key
		);
		setVoiceId( first ? String( first.id ) : '' );
	};

	const fields = (
		<Stack>
			<Toggle
				className="beyondwords--customize"
				label={ __( 'Customize', 'speechkit' ) }
				checked={ customize }
				onChange={ toggleCustomize }
			/>
			{ loadingProject && <Spinner /> }
			{ customize && ! loadingProject && (
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
			{ customize && ! loadingProject && isResolvingVoices && (
				<Spinner />
			) }
			{ customize && ! loadingProject && showModel && (
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
			{ customize && ! loadingProject && showVoice && (
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
