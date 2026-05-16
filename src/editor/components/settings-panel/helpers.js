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

// Hardcoded until the API exposes per-voice models.
// See doc/legacy-meta-migration.md and the gap discussion in s-8551.
export const MODEL_OPTIONS = [
	{ label: __( 'Standard', 'speechkit' ), value: 'standard' },
	{ label: __( 'Expressive', 'speechkit' ), value: 'expressive' },
];

// Hardcoded until the API exposes video templates.
export const VIDEO_TEMPLATE_OPTIONS = [
	{ label: __( 'Default', 'speechkit' ), value: 'default' },
];

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
