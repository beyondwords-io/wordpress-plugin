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
// its own `id`, so picking a model just means picking that variant's voice id —
// no separate model param is sent to the API.
export const ELEVENLABS_SERVICE = 'ElevenLabs';

// The variant listed first in the Model dropdown when a voice has several.
export const DEFAULT_ELEVENLABS_VOICE_MODEL_ID = 'eleven_multilingual_v2';

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
 * Coerce a possibly-non-array value (e.g. a REST error object) to an array.
 *
 * @param {*} value Possibly an array.
 *
 * @return {Array} The value if it is an array, otherwise an empty array.
 */
export function asArray( value ) {
	return Array.isArray( value ) ? value : [];
}

/**
 * The model variants for a voice.
 *
 * For ElevenLabs voices, returns every voice record sharing the same name (each
 * a distinct model), the default model first. For any other service, returns
 * the voice on its own.
 *
 * @param {Object}        voice  The selected voice record.
 * @param {Array<Object>} voices All voices for the current language.
 *
 * @return {Array<Object>} The voice's model variants.
 */
export function getVoiceModelVariants( voice, voices ) {
	if (
		voice?.service !== ELEVENLABS_SERVICE ||
		typeof voice?.model_id !== 'string'
	) {
		return voice ? [ voice ] : [];
	}

	return asArray( voices )
		.filter(
			( candidate ) =>
				candidate.name === voice.name &&
				candidate.service === ELEVENLABS_SERVICE &&
				typeof candidate.model_id === 'string'
		)
		.sort( ( a, b ) => {
			if ( a.model_id === DEFAULT_ELEVENLABS_VOICE_MODEL_ID ) {
				return -1;
			}
			if ( b.model_id === DEFAULT_ELEVENLABS_VOICE_MODEL_ID ) {
				return 1;
			}
			return 0;
		} );
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
