/**
 * WordPress dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

export const SOURCE_POST = 'post';
export const SOURCE_SCRIPT = 'script';
export const SOURCE_POST_AND_SCRIPT = 'post_and_script';

export const OUTPUT_AUDIO = 'audio';
export const OUTPUT_VIDEO = 'video';
export const OUTPUT_AUDIO_AND_VIDEO = 'audio_and_video';

export const EMBED_NONE = 'none';
export const EMBED_AUDIO_POST = 'audio_post';
export const EMBED_AUDIO_SCRIPT = 'audio_script';
export const EMBED_VIDEO_POST = 'video_post';
export const EMBED_VIDEO_SCRIPT = 'video_script';

// Empty value = defer to the project setting — the plugin omits the field from
// the content payload so the BeyondWords backend applies the project default.
export const PROJECT_DEFAULT_VALUE = '';

export function projectDefaultOption() {
	return { label: __( 'Project default', 'speechkit' ), value: '' };
}

// A language row is a (name, accent, code) triple: Language selects the name,
// Accent selects the row, and the row's CODE is the stored value.
const isValidLanguage = ( language ) =>
	!! ( language?.code && language?.name && language?.accent );

/**
 * The distinct language names across the /languages rows, in API order.
 *
 * @param {Array<Object>} languages The language rows.
 *
 * @return {Array<string>} The decoded language names.
 */
export function getLanguageNames( languages ) {
	const names = [];

	( languages ?? [] ).forEach( ( language ) => {
		if ( ! isValidLanguage( language ) ) {
			return;
		}
		const name = decodeEntities( language.name );
		if ( ! names.includes( name ) ) {
			names.push( name );
		}
	} );

	return names;
}

/**
 * The accents for a language name, as options carrying the language CODE.
 *
 * @param {Array<Object>} languages The language rows.
 * @param {string}        name      The decoded language name.
 *
 * @return {Array<{label: string, value: string}>} The Accent dropdown options.
 */
export function getAccentsForName( languages, name ) {
	if ( ! name ) {
		return [];
	}

	return ( languages ?? [] )
		.filter(
			( language ) =>
				isValidLanguage( language ) &&
				decodeEntities( language.name ) === name
		)
		.map( ( language ) => ( {
			label: decodeEntities( language.accent ),
			value: decodeEntities( language.code ),
		} ) );
}

/**
 * Find a language row by its code.
 *
 * @param {Array<Object>} languages The language rows.
 * @param {string}        code      The language code.
 *
 * @return {Object|null} The matching row, or null.
 */
export function findLanguageByCode( languages, code ) {
	if ( ! code ) {
		return null;
	}

	return (
		( languages ?? [] ).find(
			( language ) =>
				isValidLanguage( language ) &&
				decodeEntities( language.code ) === code
		) ?? null
	);
}

// Native = the language is the voice's primary one; multilingual voices merely
// support it as a secondary language and only show under "All".
export const NATIVE_ONLY = 'native';
export const NATIVE_ALL = 'all';

/**
 * A voice's primary (native) language code.
 *
 * @param {Object} voice A voice record.
 *
 * @return {string} The primary language code, or '' when unknown.
 */
export function voicePrimaryCode( voice ) {
	const language = voice?.language;

	if ( typeof language === 'string' ) {
		return language;
	}
	if ( language && typeof language === 'object' && language.code ) {
		return language.code;
	}
	return voice?.languages?.[ 0 ]?.code || '';
}

/**
 * Whether a voice is native to a language code. A voice with no determinable
 * primary language counts as native, so we never hide what we cannot classify.
 *
 * @param {Object} voice A voice record.
 * @param {string} code  The language code.
 *
 * @return {boolean} Whether the voice is native to the code.
 */
export function voiceIsNative( voice, code ) {
	const primary = voicePrimaryCode( voice );
	if ( ! primary ) {
		return true;
	}
	return String( primary ) === String( code );
}

/**
 * Apply the Native filter to a language's voices.
 *
 * `keepId` is always kept, so changing the filter never drops the saved voice.
 *
 * @param {Array<Object>} voices       All fetched voices for the language.
 * @param {string}        code         The selected language code.
 * @param {string}        nativeFilter NATIVE_ONLY or NATIVE_ALL.
 * @param {string}        keepId       The voice id to always keep, or ''.
 *
 * @return {Array<Object>} The filtered voices.
 */
export function filterVoicesByNative( voices, code, nativeFilter, keepId ) {
	const list = voices ?? [];

	let result =
		nativeFilter === NATIVE_ALL
			? list
			: list.filter( ( voice ) => voiceIsNative( voice, code ) );

	if (
		keepId &&
		! result.some( ( voice ) => String( voice.id ) === String( keepId ) )
	) {
		const saved = list.find(
			( voice ) => String( voice.id ) === String( keepId )
		);
		if ( saved ) {
			result = [ ...result, saved ];
		}
	}

	return result;
}

// Voice "models" only exist for ElevenLabs voices; each (name, model_id) pair is
// a distinct voice record, and the chosen voice id is the only value sent to the API.
export const ELEVENLABS_SERVICE = 'ElevenLabs';

// The model listed first in the Model dropdown.
export const DEFAULT_ELEVENLABS_VOICE_MODEL_ID = 'eleven_multilingual_v2';

// Bucket key for voices without an ElevenLabs `model_id` (e.g. standard voices).
export const STANDARD_MODEL_KEY = 'standard';

// Human labels for the known ElevenLabs model slugs.
const VOICE_MODEL_LABELS = {
	eleven_v3: __( 'v3', 'speechkit' ),
	eleven_multilingual_v2: __( 'Multilingual v2', 'speechkit' ),
	eleven_flash_v2_5: __( 'Flash v2.5', 'speechkit' ),
	eleven_turbo_v2_5: __( 'Turbo v2.5', 'speechkit' ),
};

/**
 * Human label for a voice model_id slug.
 *
 * @param {string} modelId The model_id slug (e.g. `eleven_flash_v2_5`).
 *
 * @return {string} A display label.
 */
export function voiceModelLabel( modelId ) {
	if ( VOICE_MODEL_LABELS[ modelId ] ) {
		return VOICE_MODEL_LABELS[ modelId ];
	}
	return String( modelId )
		.replace( /^eleven_/, '' )
		.replace( /_/g, ' ' )
		.replace( /\b\w/g, ( c ) => c.toUpperCase() );
}

/**
 * The model bucket key for a voice.
 *
 * ElevenLabs voices key by `model_id`; all others share the Standard bucket.
 *
 * @param {Object} voice A voice record.
 *
 * @return {string} The model bucket key.
 */
export function voiceModelKey( voice ) {
	if (
		voice?.service === ELEVENLABS_SERVICE &&
		typeof voice?.model_id === 'string'
	) {
		return voice.model_id;
	}
	return STANDARD_MODEL_KEY;
}

/**
 * The distinct model buckets across a language's voices, for the Model dropdown.
 *
 * ElevenLabs models first (the default leading), then a single Standard bucket.
 *
 * @param {Array<Object>} voices All voices for the current language.
 *
 * @return {Array<{key: string, label: string}>} The Model dropdown options.
 */
export function getLanguageModels( voices ) {
	const modelIds = [];
	let hasStandard = false;

	( voices ?? [] ).forEach( ( voice ) => {
		const key = voiceModelKey( voice );
		if ( key === STANDARD_MODEL_KEY ) {
			hasStandard = true;
		} else if ( ! modelIds.includes( key ) ) {
			modelIds.push( key );
		}
	} );

	// Stable sort (V8): the default model leads, the rest keep API order.
	modelIds.sort( ( a, b ) => {
		if ( a === DEFAULT_ELEVENLABS_VOICE_MODEL_ID ) {
			return -1;
		}
		if ( b === DEFAULT_ELEVENLABS_VOICE_MODEL_ID ) {
			return 1;
		}
		return 0;
	} );

	const models = modelIds.map( ( key ) => ( {
		key,
		label: voiceModelLabel( key ),
	} ) );

	if ( hasStandard ) {
		models.push( {
			key: STANDARD_MODEL_KEY,
			label: __( 'Legacy', 'speechkit' ),
		} );
	}

	return models;
}

export function getSourceOptions() {
	return [
		{ label: __( 'Post', 'speechkit' ), value: SOURCE_POST },
		{ label: __( 'Script', 'speechkit' ), value: SOURCE_SCRIPT },
		{
			label: __( 'Post + script', 'speechkit' ),
			value: SOURCE_POST_AND_SCRIPT,
		},
	];
}

export function getOutputOptions() {
	return [
		{ label: __( 'Audio', 'speechkit' ), value: OUTPUT_AUDIO },
		{ label: __( 'Video', 'speechkit' ), value: OUTPUT_VIDEO },
		{
			label: __( 'Audio + video', 'speechkit' ),
			value: OUTPUT_AUDIO_AND_VIDEO,
		},
	];
}

export function sourceIncludesPost( source ) {
	return source === SOURCE_POST || source === SOURCE_POST_AND_SCRIPT;
}

export function sourceIncludesScript( source ) {
	return source === SOURCE_SCRIPT || source === SOURCE_POST_AND_SCRIPT;
}

export function outputIncludesAudio( output ) {
	return output === OUTPUT_AUDIO || output === OUTPUT_AUDIO_AND_VIDEO;
}

export function outputIncludesVideo( output ) {
	return output === OUTPUT_VIDEO || output === OUTPUT_AUDIO_AND_VIDEO;
}

/**
 * Derive the valid "Embed" dropdown options from the current Source × Output.
 *
 * Returns None plus one entry per asset the current source/output would produce.
 *
 * @param {string} source One of SOURCE_*.
 * @param {string} output One of OUTPUT_*.
 *
 * @return {Array<{label: string, value: string}>} SelectControl options.
 */
export function getEmbedOptions( source, output ) {
	const options = [ { label: __( 'None', 'speechkit' ), value: EMBED_NONE } ];

	if ( outputIncludesAudio( output ) ) {
		if ( sourceIncludesPost( source ) ) {
			options.push( {
				label: __( 'Audio (post)', 'speechkit' ),
				value: EMBED_AUDIO_POST,
			} );
		}
		if ( sourceIncludesScript( source ) ) {
			options.push( {
				label: __( 'Audio (script)', 'speechkit' ),
				value: EMBED_AUDIO_SCRIPT,
			} );
		}
	}

	if ( outputIncludesVideo( output ) ) {
		if ( sourceIncludesPost( source ) ) {
			options.push( {
				label: __( 'Video (post)', 'speechkit' ),
				value: EMBED_VIDEO_POST,
			} );
		}
		if ( sourceIncludesScript( source ) ) {
			options.push( {
				label: __( 'Video (script)', 'speechkit' ),
				value: EMBED_VIDEO_SCRIPT,
			} );
		}
	}

	return options;
}

/**
 * Whether the given embed value is selectable for the current Source × Output.
 *
 * @param {string} embed  One of EMBED_*.
 * @param {string} source One of SOURCE_*.
 * @param {string} output One of OUTPUT_*.
 *
 * @return {boolean} True when embed is in the current option list.
 */
export function isEmbedValid( embed, source, output ) {
	return getEmbedOptions( source, output ).some(
		( option ) => option.value === embed
	);
}

/**
 * The default Embed for a post that hasn't chosen one: the first produced asset.
 *
 * Keeps the player visible by default — "None" is the deliberate opt-out.
 *
 * @param {string} source One of SOURCE_*.
 * @param {string} output One of OUTPUT_*.
 *
 * @return {string} The default embed value.
 */
export function getDefaultEmbed( source, output ) {
	const asset = getEmbedOptions( source, output ).find(
		( option ) => option.value !== EMBED_NONE
	);

	return asset ? asset.value : EMBED_NONE;
}
