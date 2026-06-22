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
import { asArray, getLanguageModels, voiceModelKey } from './helpers';
import Stack from '../stack';
import Toggle from '../toggle';

export function VoiceSection( { withPanel = true } ) {
	const postType = useSelect(
		( s ) => s( 'core/editor' ).getCurrentPostType(),
		[]
	);

	// `useEntityProp` returns `{}` (so `meta` is undefined) until the post entity
	// record is hydrated; default to an empty object before reading meta values.
	const [ rawMeta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const meta = rawMeta ?? {};

	const languageCode = meta.beyondwords_language_code || '';
	const voiceId = meta.beyondwords_body_voice_id || '';

	// "Customize" is opt-in. A post counts as customised as soon as it carries an
	// explicit language or voice; otherwise it uses the project defaults, we show
	// no fields and send nothing. Held in local state so toggling on can reveal
	// the pickers before any choice has been made.
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

	// When Customize is turned on for a post with no explicit choice yet, fetch
	// the project's default language and pre-select it. We populate only the
	// Language — the user picks the Voice (a project default voice can belong to
	// a different language than the one we list). On failure we leave the
	// Language empty and fall back to the manual "pick a language" flow.
	const needsDefault = customize && ! languageCode && ! voiceId;

	const project = useSelect(
		( s ) =>
			needsDefault
				? s( 'beyondwords/settings' ).getProject( projectId )
				: null,
		[ needsDefault, projectId ]
	);

	// True once the project fetch has settled (resolved or failed). Keeps the
	// spinner up and the Language hidden until we know the default — avoids a
	// one-frame "empty dropdown then spinner" flicker on the first render.
	const projectResolved = useSelect(
		( s ) =>
			! needsDefault ||
			s( 'beyondwords/settings' ).hasFinishedResolution( 'getProject', [
				projectId,
			] ),
		[ needsDefault, projectId ]
	);

	// Apply the resolved default language. Guarded by needsDefault so it never
	// overrides an explicit choice, and sets only the language (no voice). Reads
	// the freshest meta — not the closure's — so a concurrent edit to another
	// field during the async fetch isn't clobbered.
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

	// Languages and voices are only needed while customising, so they are fetched
	// lazily behind the toggle — a default post makes no language/voice API calls.
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

	// Changing the Language re-fetches its voices; show a Spinner in place of the
	// Voice/Model dropdowns until that resolves.
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

	// Picking a Language sets the language *and* seeds the voice with that
	// language's default body voice, so a concrete voice is always stored (we
	// never send the language itself — the voice carries it). Languages with no
	// default voice fall back to the "Select a voice" placeholder.
	const setLanguageCode = ( value ) => {
		const language = asArray( languages ).find(
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
		...asArray( languages ).map( ( language ) => ( {
			label: `${ decodeEntities( language.name ) } (${ decodeEntities(
				language.accent
			) })`,
			value: decodeEntities( language.code ),
		} ) ),
	];

	// "Model" is a language-level filter: each ElevenLabs model_id, plus a single
	// "Standard" bucket for non-ElevenLabs voices. Picking a model narrows the
	// Voice list to the voices that offer it. With a single bucket there is no
	// Model dropdown and every voice is listed.
	const models = getLanguageModels( voices );
	const showModel = models.length > 1;

	const selectedVoice = asArray( voices ).find(
		( voice ) => String( voice.id ) === String( voiceId )
	);
	// The selected model is derived from the selected voice (we persist only the
	// voice id); empty until a voice — and therefore a model — is chosen.
	const selectedModelKey = selectedVoice
		? voiceModelKey( selectedVoice )
		: '';

	const bucketVoices = showModel
		? asArray( voices ).filter(
				( voice ) => voiceModelKey( voice ) === selectedModelKey
		  )
		: asArray( voices );

	const hasVoices = asArray( voices ).length > 0;

	// Model gates the Voice list: hide Voice until a model is chosen. The
	// single-bucket case has no Model dropdown, so Voice shows immediately.
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
		const first = asArray( voices ).find(
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
