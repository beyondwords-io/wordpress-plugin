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
import { asArray, getVoiceModelVariants, voiceModelLabel } from './helpers';
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

	// Voice dropdown lists distinct voice names. ElevenLabs voices repeat a name
	// once per model, so deduping by name keeps the list clean; the Model
	// dropdown then selects the actual variant (voice id) within a name.
	const voicesByName = {};
	const voiceNames = [];
	asArray( voices ).forEach( ( voice ) => {
		if ( ! voicesByName[ voice.name ] ) {
			voicesByName[ voice.name ] = [];
			voiceNames.push( voice.name );
		}
		voicesByName[ voice.name ].push( voice );
	} );

	const selectedVoice = asArray( voices ).find(
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

	const hasVoices = asArray( voices ).length > 0;

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
			{ customize && ! loadingProject && hasVoices && (
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
			{ customize && ! loadingProject && showModel && (
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
