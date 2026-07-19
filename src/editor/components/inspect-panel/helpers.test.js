/* global describe, it, expect */

/**
 * Internal dependencies
 */
import { SYSTEM_META_KEYS, hasBeyondwordsData, getTextToCopy } from './helpers';

// Representative subset of the key lists PHP supplies via the settings store.
const CURRENT = [ 'beyondwords_generate_audio', 'beyondwords_content_id' ];
const DEPRECATED = [ 'speechkit_podcast_id' ];
const DATA_KEYS = [ ...CURRENT, ...DEPRECATED ];

describe( 'hasBeyondwordsData', () => {
	it( 'is false when none of the data keys are populated', () => {
		expect( hasBeyondwordsData( {}, DATA_KEYS ) ).toBe( false );
	} );

	it( 'ignores keys outside the supplied list (e.g. system fields)', () => {
		expect(
			hasBeyondwordsData(
				{ plugin_version: '5.0.0', wp_version: '6.5', wp_post_id: 123 },
				DATA_KEYS
			)
		).toBe( false );
	} );

	it( 'is true when a current data key is populated', () => {
		expect(
			hasBeyondwordsData( { beyondwords_content_id: '12345' }, DATA_KEYS )
		).toBe( true );
	} );

	it( 'is true when only a deprecated data key is populated', () => {
		expect(
			hasBeyondwordsData( { speechkit_podcast_id: '999' }, DATA_KEYS )
		).toBe( true );
	} );

	it( 'treats empty strings as no data', () => {
		const empty = { beyondwords_content_id: '', speechkit_podcast_id: '' };
		expect( hasBeyondwordsData( empty, DATA_KEYS ) ).toBe( false );
	} );

	it( 'reflects data added after mount (the stale-snapshot regression)', () => {
		expect(
			hasBeyondwordsData( { plugin_version: '5.0.0' }, DATA_KEYS )
		).toBe( false );
		expect(
			hasBeyondwordsData( { beyondwords_content_id: '12345' }, DATA_KEYS )
		).toBe( true );
	} );

	it( 'is false before the keys load (settings not yet fetched)', () => {
		expect(
			hasBeyondwordsData( { beyondwords_content_id: '12345' } )
		).toBe( false );
	} );
} );

describe( 'getTextToCopy', () => {
	it( 'labels each field with its meta key, in the supplied order', () => {
		const text = getTextToCopy(
			{ beyondwords_content_id: '12345' },
			CURRENT,
			DEPRECATED
		);
		expect( text ).toContain( 'beyondwords_content_id\r\n12345' );
		expect( text.indexOf( 'beyondwords_content_id' ) ).toBeGreaterThan(
			text.indexOf( 'beyondwords_generate_audio' )
		);
	} );

	it( 'groups the sections in order with separators', () => {
		const text = getTextToCopy( {}, CURRENT, DEPRECATED );
		const deprecated = text.indexOf( '=== Deprecated ===' );
		const system = text.indexOf( '=== System ===' );
		expect( deprecated ).toBeGreaterThan( -1 );
		expect( system ).toBeGreaterThan( deprecated );
		expect( text ).toContain( '=== Copied using the Block Editor ===' );
	} );

	it( 'always appends the system fields', () => {
		const text = getTextToCopy(
			{ plugin_version: '5.0.0' },
			CURRENT,
			DEPRECATED
		);
		SYSTEM_META_KEYS.forEach( ( key ) => expect( text ).toContain( key ) );
		expect( text ).toContain( 'plugin_version\r\n5.0.0' );
	} );

	it( 'reads the same meta hasBeyondwordsData checks', () => {
		const meta = { beyondwords_content_id: '12345' };
		expect( getTextToCopy( meta, CURRENT, DEPRECATED ) ).toContain(
			'12345'
		);
		expect( hasBeyondwordsData( meta, DATA_KEYS ) ).toBe( true );
	} );
} );
