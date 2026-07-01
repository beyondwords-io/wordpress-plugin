/**
 * WordPress dependencies
 */
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

// Empty value = defer to the project setting. The plugin omits the field from
// the content payload when empty, so the BeyondWords backend applies the
// project default. Prepend this to leaf-setting dropdowns.
export const PROJECT_DEFAULT_VALUE = '';

export function projectDefaultOption() {
	return { label: __( 'Project default', 'speechkit' ), value: '' };
}

// Voice "models" only exist for ElevenLabs voices, exposed as a snake_case
// `model_id` slug. Each (name, model_id) pair is a distinct voice record with
// its own `id`. The Model dropdown is a language-level filter: picking a model
// narrows the Voice list to the voices that offer it, then the chosen voice id
// (which carries the model) is the only value sent to the API. Voices from any
// other service have no `model_id` and share a single "Standard" bucket.
export const ELEVENLABS_SERVICE = 'ElevenLabs';

// The model listed first in the Model dropdown.
export const DEFAULT_ELEVENLABS_VOICE_MODEL_ID = 'eleven_multilingual_v2';

// Bucket key for voices without an ElevenLabs `model_id` (e.g. standard voices).
export const STANDARD_MODEL_KEY = 'standard';

// Human labels for the known ElevenLabs model slugs. Unknown slugs fall back to
// a title-cased version of the slug minus the `eleven_` prefix.
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
 * ElevenLabs voices key by their `model_id`; every other voice falls into the
 * shared STANDARD_MODEL_KEY bucket.
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
 * The distinct model buckets offered across a language's voices, as
 * `{ key, label }` options for the Model dropdown.
 *
 * ElevenLabs models come first (the default model leading), followed by a
 * single "Standard" bucket when any non-ElevenLabs voices are present.
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
 * Returns None plus one entry for each asset combination the current
 * source/output would produce. The Embed value persisted to post meta picks
 * one of these to render on the published post.
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
 * The default Embed value for a post that hasn't chosen one yet: the first asset
 * the current Source × Output produces (e.g. Post × Audio → "Audio (post)").
 * This keeps the player visible by default — "None" is the deliberate opt-out.
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
