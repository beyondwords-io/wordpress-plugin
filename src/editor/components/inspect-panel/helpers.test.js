/* global describe, it, expect */

/**
 * Internal dependencies
 */
import {
	CURRENT_META_KEYS,
	DEPRECATED_META_KEYS,
	SYSTEM_META_KEYS,
	hasBeyondwordsData,
	getTextToCopy,
} from './helpers';

describe( 'hasBeyondwordsData', () => {
	it( 'is false when nothing is populated', () => {
		expect( hasBeyondwordsData( {} ) ).toBe( false );
	} );

	it( 'ignores the always-present system fields', () => {
		// A fresh post once settings have loaded: only the versions/post id are
		// set. Counting these would leave Remove permanently enabled.
		expect(
			hasBeyondwordsData( {
				plugin_version: '5.0.0',
				wp_version: '6.5',
				wp_post_id: 123,
			} )
		).toBe( false );
	} );

	it( 'is true when a current data field is populated', () => {
		expect(
			hasBeyondwordsData( { beyondwords_content_id: '12345' } )
		).toBe( true );
	} );

	it( 'is true when only a deprecated data field is populated', () => {
		expect( hasBeyondwordsData( { speechkit_podcast_id: '999' } ) ).toBe(
			true
		);
	} );

	it( 'treats empty strings as no data', () => {
		const empty = Object.fromEntries(
			[ ...CURRENT_META_KEYS, ...DEPRECATED_META_KEYS ].map( ( key ) => [
				key,
				'',
			] )
		);
		expect( hasBeyondwordsData( empty ) ).toBe( false );
	} );

	it( 'reflects data added after mount (the stale-snapshot regression)', () => {
		// Mount state: no BeyondWords data, settings loaded.
		const atMount = { plugin_version: '5.0.0', wp_version: '6.5' };
		expect( hasBeyondwordsData( atMount ) ).toBe( false );

		// Audio generated → content id now present in the live meta.
		const afterGenerate = { ...atMount, beyondwords_content_id: '12345' };
		expect( hasBeyondwordsData( afterGenerate ) ).toBe( true );
	} );
} );

describe( 'getTextToCopy', () => {
	it( 'labels each field with its meta key', () => {
		const text = getTextToCopy( { beyondwords_content_id: '12345' } );
		expect( text ).toContain( 'beyondwords_content_id\r\n12345' );
	} );

	it( 'groups the sections in order', () => {
		const text = getTextToCopy( {} );
		const deprecated = text.indexOf( '=== Deprecated ===' );
		const system = text.indexOf( '=== System ===' );
		expect( deprecated ).toBeGreaterThan( -1 );
		expect( system ).toBeGreaterThan( deprecated );
		expect( text ).toContain( '=== Copied using the Block Editor ===' );
	} );

	it( 'includes every known field as a label', () => {
		const text = getTextToCopy( {} );
		[
			...CURRENT_META_KEYS,
			...DEPRECATED_META_KEYS,
			...SYSTEM_META_KEYS,
		].forEach( ( key ) => {
			expect( text ).toContain( key );
		} );
	} );

	it( 'reads the same data the Copy and Remove controls share', () => {
		// Both controls derive from the passed-in meta, so a populated field
		// shows up in the payload and flips hasBeyondwordsData together.
		const meta = { beyondwords_content_id: '12345' };
		expect( getTextToCopy( meta ) ).toContain( '12345' );
		expect( hasBeyondwordsData( meta ) ).toBe( true );
	} );
} );
