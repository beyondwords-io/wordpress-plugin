/* global describe, it, expect, beforeEach, jest */

/*
 * classic-metabox.js is a bare IIFE that wires itself to the rendered metabox, so
 * these tests build the markup PHP would emit, re-run the module against it, and
 * drive it through the same `change` events a user would.
 */

const SOURCE_VALUES = [ 'post', 'script', 'post_and_script' ];
const OUTPUT_VALUES = [ 'audio', 'video', 'audio_and_video' ];

const renderSelect = ( id, values, selected ) =>
	`<select id="${ id }" name="${ id }">${ values
		.map(
			( value ) =>
				`<option value="${ value }"${
					value === selected ? ' selected' : ''
				}>${ value }</option>`
		)
		.join( '' ) }</select>`;

/**
 * Render the metabox fields and boot the module against them.
 *
 * @param {Object}        options              Fixture options.
 * @param {string}        options.source       The selected Source.
 * @param {string}        options.output       The selected Output.
 * @param {Array<string>} options.embedOptions The Embed options PHP rendered.
 * @param {string}        options.embed        The selected Embed.
 * @param {boolean}       options.withTouched  Whether the touched flag is present.
 *
 * @return {Object} The wired elements.
 */
const boot = ( {
	source = 'post',
	output = 'audio',
	embedOptions = [ 'none', 'audio_post' ],
	embed = 'audio_post',
	withTouched = true,
} = {} ) => {
	document.body.innerHTML = [
		renderSelect( 'beyondwords_source', SOURCE_VALUES, source ),
		renderSelect( 'beyondwords_output', OUTPUT_VALUES, output ),
		renderSelect( 'beyondwords_embed', embedOptions, embed ),
		withTouched
			? '<input type="hidden" id="beyondwords_embed_touched" name="beyondwords_embed_touched" value="" />'
			: '',
		// The wrappers the Source/Output handlers show and hide.
		'<div id="beyondwords-metabox-settings--beyondwords-script-template-id"></div>',
		'<div id="beyondwords-metabox-settings--beyondwords-video-template-id"></div>',
		'<div id="beyondwords-metabox-settings--beyondwords-video-size"></div>',
	].join( '' );

	require( './classic-metabox' );

	return {
		source: document.getElementById( 'beyondwords_source' ),
		output: document.getElementById( 'beyondwords_output' ),
		embed: document.getElementById( 'beyondwords_embed' ),
		touched: document.getElementById( 'beyondwords_embed_touched' ),
	};
};

const change = ( element, value ) => {
	element.value = value;
	element.dispatchEvent( new Event( 'change', { bubbles: true } ) );
};

const optionValues = ( select ) =>
	Array.from( select.options ).map( ( option ) => option.value );

describe( 'classic-metabox recomputeEmbed', () => {
	beforeEach( () => {
		jest.resetModules();
	} );

	it( 'selects the first produced asset when the stored value is invalid', () => {
		const { output, embed } = boot( { embed: 'audio_post' } );

		change( output, 'video' );

		// audio_post cannot be produced by Post × Video, so the post keeps a
		// player on the default asset rather than falling through to None.
		expect( optionValues( embed ) ).toEqual( [ 'none', 'video_post' ] );
		expect( embed.value ).toBe( 'video_post' );
	} );

	it( 'keeps a value the new Source × Output can still produce', () => {
		const { source, embed } = boot( {
			output: 'audio_and_video',
			embedOptions: [ 'none', 'audio_post', 'video_post' ],
			embed: 'video_post',
		} );

		change( source, 'post_and_script' );

		expect( embed.value ).toBe( 'video_post' );
	} );

	it( 'keeps an explicit None, which every Source × Output offers', () => {
		const { output, embed } = boot( { embed: 'none' } );

		change( output, 'video' );

		expect( embed.value ).toBe( 'none' );
	} );

	it( 'falls back to None when no asset can be produced', () => {
		// Defensive: the rendered option list always has at least one asset, but
		// getDefaultEmbed must still yield a selectable value if it ever does not.
		const { output, embed } = boot( {
			embedOptions: [ 'none' ],
			embed: 'none',
		} );

		change( output, 'audio' );

		expect( embed.value ).toBe( 'none' );
	} );
} );

describe( 'classic-metabox embed touched flag', () => {
	beforeEach( () => {
		jest.resetModules();
	} );

	it( 'is set when the user picks an Embed', () => {
		const { embed, touched } = boot();

		expect( touched.value ).toBe( '' );

		change( embed, 'none' );

		expect( touched.value ).toBe( '1' );
	} );

	it( 'stays empty when an Output change rebuilds the Embed', () => {
		const { output, embed, touched } = boot( { embed: 'audio_post' } );

		change( output, 'video' );

		// The rebuild picked video_post, but the user never chose it — so save()
		// leaves the meta unset and re-derives it, matching the block editor.
		expect( embed.value ).toBe( 'video_post' );
		expect( touched.value ).toBe( '' );
	} );

	it( 'stays empty when a Source change rebuilds the Embed', () => {
		const { source, touched } = boot( {
			source: 'post',
			embedOptions: [ 'none', 'audio_post' ],
			embed: 'audio_post',
		} );

		change( source, 'script' );

		expect( touched.value ).toBe( '' );
	} );

	it( 'is not required for the Embed to be recomputed', () => {
		const { output, embed } = boot( { withTouched: false } );

		change( output, 'video' );

		expect( embed.value ).toBe( 'video_post' );
	} );
} );
