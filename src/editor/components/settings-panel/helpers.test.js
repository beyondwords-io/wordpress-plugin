/* global describe, it, expect */

/**
 * Internal dependencies
 */
import {
	SOURCE_POST,
	SOURCE_SCRIPT,
	SOURCE_POST_AND_SCRIPT,
	OUTPUT_AUDIO,
	OUTPUT_VIDEO,
	OUTPUT_AUDIO_AND_VIDEO,
	EMBED_NONE,
	EMBED_AUDIO_POST,
	EMBED_AUDIO_SCRIPT,
	EMBED_VIDEO_POST,
	EMBED_VIDEO_SCRIPT,
	PROJECT_DEFAULT_VALUE,
	DEFAULT_ELEVENLABS_VOICE_MODEL_ID,
	projectDefaultOption,
	voiceModelLabel,
	getVoiceModelVariants,
	getSourceOptions,
	getOutputOptions,
	sourceIncludesPost,
	sourceIncludesScript,
	outputIncludesAudio,
	outputIncludesVideo,
	getEmbedOptions,
	isEmbedValid,
	getDefaultEmbed,
} from './helpers';

describe( 'projectDefaultOption', () => {
	it( 'is an empty-valued option', () => {
		expect( projectDefaultOption() ).toEqual( {
			label: 'Project default',
			value: '',
		} );
		expect( PROJECT_DEFAULT_VALUE ).toBe( '' );
	} );
} );

describe( 'voiceModelLabel', () => {
	it( 'maps known ElevenLabs model slugs', () => {
		expect( voiceModelLabel( 'eleven_v3' ) ).toBe( 'v3' );
		expect( voiceModelLabel( 'eleven_multilingual_v2' ) ).toBe(
			'Multilingual v2'
		);
		expect( voiceModelLabel( 'eleven_flash_v2_5' ) ).toBe( 'Flash v2.5' );
		expect( voiceModelLabel( 'eleven_turbo_v2_5' ) ).toBe( 'Turbo v2.5' );
	} );

	it( 'title-cases unknown slugs and strips the eleven_ prefix', () => {
		expect( voiceModelLabel( 'eleven_something_new' ) ).toBe(
			'Something New'
		);
		expect( voiceModelLabel( 'custom_model' ) ).toBe( 'Custom Model' );
	} );
} );

describe( 'getVoiceModelVariants', () => {
	const adamFlash = {
		id: 8602,
		name: 'Adam E',
		service: 'ElevenLabs',
		model_id: 'eleven_flash_v2_5',
	};
	const adamMulti = {
		id: 8603,
		name: 'Adam E',
		service: 'ElevenLabs',
		model_id: 'eleven_multilingual_v2',
	};
	const adamV3 = {
		id: 9257,
		name: 'Adam E',
		service: 'ElevenLabs',
		model_id: 'eleven_v3',
	};
	const azure = {
		id: 1,
		name: 'James',
		service: 'Azure',
		model_id: null,
	};
	const voices = [ adamFlash, adamMulti, adamV3, azure ];

	it( 'returns the voice alone for non-ElevenLabs services', () => {
		expect( getVoiceModelVariants( azure, voices ) ).toEqual( [ azure ] );
	} );

	it( 'returns the voice alone when model_id is not a string', () => {
		const noModel = { ...adamFlash, model_id: null };
		expect( getVoiceModelVariants( noModel, voices ) ).toEqual( [
			noModel,
		] );
	} );

	it( 'groups same-name ElevenLabs voices as model variants', () => {
		const variants = getVoiceModelVariants( adamV3, voices );
		expect( variants ).toHaveLength( 3 );
		expect( variants.map( ( v ) => v.id ).sort() ).toEqual( [
			8602, 8603, 9257,
		] );
	} );

	it( 'sorts the default model first', () => {
		const variants = getVoiceModelVariants( adamV3, voices );
		expect( variants[ 0 ].model_id ).toBe(
			DEFAULT_ELEVENLABS_VOICE_MODEL_ID
		);
		expect( variants[ 0 ].id ).toBe( 8603 );
	} );

	it( 'does not mix in voices with a different name', () => {
		const variants = getVoiceModelVariants( adamV3, voices );
		expect( variants.every( ( v ) => v.name === 'Adam E' ) ).toBe( true );
	} );

	it( 'returns an empty array for a missing voice', () => {
		expect( getVoiceModelVariants( undefined, voices ) ).toEqual( [] );
	} );
} );

describe( 'source/output predicates', () => {
	it( 'sourceIncludesPost', () => {
		expect( sourceIncludesPost( SOURCE_POST ) ).toBe( true );
		expect( sourceIncludesPost( SOURCE_POST_AND_SCRIPT ) ).toBe( true );
		expect( sourceIncludesPost( SOURCE_SCRIPT ) ).toBe( false );
	} );

	it( 'sourceIncludesScript', () => {
		expect( sourceIncludesScript( SOURCE_SCRIPT ) ).toBe( true );
		expect( sourceIncludesScript( SOURCE_POST_AND_SCRIPT ) ).toBe( true );
		expect( sourceIncludesScript( SOURCE_POST ) ).toBe( false );
	} );

	it( 'outputIncludesAudio', () => {
		expect( outputIncludesAudio( OUTPUT_AUDIO ) ).toBe( true );
		expect( outputIncludesAudio( OUTPUT_AUDIO_AND_VIDEO ) ).toBe( true );
		expect( outputIncludesAudio( OUTPUT_VIDEO ) ).toBe( false );
	} );

	it( 'outputIncludesVideo', () => {
		expect( outputIncludesVideo( OUTPUT_VIDEO ) ).toBe( true );
		expect( outputIncludesVideo( OUTPUT_AUDIO_AND_VIDEO ) ).toBe( true );
		expect( outputIncludesVideo( OUTPUT_AUDIO ) ).toBe( false );
	} );

	it( 'getSourceOptions / getOutputOptions expose the expected values', () => {
		expect( getSourceOptions().map( ( o ) => o.value ) ).toEqual( [
			SOURCE_POST,
			SOURCE_SCRIPT,
			SOURCE_POST_AND_SCRIPT,
		] );
		expect( getOutputOptions().map( ( o ) => o.value ) ).toEqual( [
			OUTPUT_AUDIO,
			OUTPUT_VIDEO,
			OUTPUT_AUDIO_AND_VIDEO,
		] );
	} );
} );

describe( 'getEmbedOptions', () => {
	const values = ( source, output ) =>
		getEmbedOptions( source, output ).map( ( o ) => o.value );

	it( 'Post + Audio → None / Audio (post)', () => {
		expect( values( SOURCE_POST, OUTPUT_AUDIO ) ).toEqual( [
			EMBED_NONE,
			EMBED_AUDIO_POST,
		] );
	} );

	it( 'Post + Script × Audio + Video → all four assets', () => {
		expect(
			values( SOURCE_POST_AND_SCRIPT, OUTPUT_AUDIO_AND_VIDEO )
		).toEqual( [
			EMBED_NONE,
			EMBED_AUDIO_POST,
			EMBED_AUDIO_SCRIPT,
			EMBED_VIDEO_POST,
			EMBED_VIDEO_SCRIPT,
		] );
	} );

	it( 'Script + Video → None / Video (script)', () => {
		expect( values( SOURCE_SCRIPT, OUTPUT_VIDEO ) ).toEqual( [
			EMBED_NONE,
			EMBED_VIDEO_SCRIPT,
		] );
	} );

	it( 'always includes None first', () => {
		expect( values( SOURCE_POST, OUTPUT_AUDIO )[ 0 ] ).toBe( EMBED_NONE );
	} );
} );

describe( 'isEmbedValid', () => {
	it( 'accepts an asset the source/output produces', () => {
		expect(
			isEmbedValid( EMBED_AUDIO_POST, SOURCE_POST, OUTPUT_AUDIO )
		).toBe( true );
	} );

	it( 'rejects an asset the source/output cannot produce', () => {
		expect(
			isEmbedValid( EMBED_VIDEO_SCRIPT, SOURCE_POST, OUTPUT_AUDIO )
		).toBe( false );
	} );

	it( 'always accepts None', () => {
		expect( isEmbedValid( EMBED_NONE, SOURCE_POST, OUTPUT_AUDIO ) ).toBe(
			true
		);
	} );
} );

describe( 'getDefaultEmbed', () => {
	it( 'returns the first asset for Post + Audio', () => {
		expect( getDefaultEmbed( SOURCE_POST, OUTPUT_AUDIO ) ).toBe(
			EMBED_AUDIO_POST
		);
	} );

	it( 'returns the first video asset for Post + Video', () => {
		expect( getDefaultEmbed( SOURCE_POST, OUTPUT_VIDEO ) ).toBe(
			EMBED_VIDEO_POST
		);
	} );

	it( 'returns a non-None value for every source/output combination', () => {
		[ SOURCE_POST, SOURCE_SCRIPT, SOURCE_POST_AND_SCRIPT ].forEach(
			( source ) => {
				[ OUTPUT_AUDIO, OUTPUT_VIDEO, OUTPUT_AUDIO_AND_VIDEO ].forEach(
					( output ) => {
						expect( getDefaultEmbed( source, output ) ).not.toBe(
							EMBED_NONE
						);
					}
				);
			}
		);
	} );
} );
