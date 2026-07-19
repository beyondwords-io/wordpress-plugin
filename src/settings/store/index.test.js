/* global jest, describe, it, expect, beforeEach */

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createRegistry } from '@wordpress/data';

/**
 * Internal dependencies
 */
import store from './index';

jest.mock( '@wordpress/api-fetch' );

const STORE = 'beyondwords/settings';

// Distinct data per resolver argument, so a value leaking from one argument's
// fetch into another's slot is observable in the assertions below.
const VOICES = {
	en_US: [ { id: 1, name: 'Amy' } ],
	fr_FR: [ { id: 2, name: 'Céline' } ],
};

const PROJECT = {
	11111: { id: 11111, language: 'en_US' },
	22222: { id: 22222, language: 'fr_FR' },
};

const VIDEO_SIZES = {
	11111: [ { name: 'square', enabled: true } ],
	22222: [ { name: 'portrait', enabled: true } ],
};

describe( 'beyondwords/settings store', () => {
	let registry;

	beforeEach( () => {
		apiFetch.mockReset();
		apiFetch.mockImplementation( ( { path } ) => {
			let m = path.match( /languages\/([^/]+)\/voices$/ );
			if ( m ) {
				return Promise.resolve( VOICES[ m[ 1 ] ] ?? [] );
			}
			m = path.match( /projects\/([^/]+)\/video-settings$/ );
			if ( m ) {
				return Promise.resolve( {
					sizes: VIDEO_SIZES[ m[ 1 ] ] ?? [],
				} );
			}
			m = path.match( /projects\/([^/]+)$/ );
			if ( m ) {
				return Promise.resolve( PROJECT[ m[ 1 ] ] ?? {} );
			}
			return Promise.resolve( [] );
		} );

		registry = createRegistry();
		registry.register( store );
	} );

	describe( 'getVoices', () => {
		it( 'keeps each language in its own slot after another is fetched', async () => {
			const resolve = registry.resolveSelect( STORE );

			// Fetch A, then B. With the old single shared `voices` slot, B's
			// response overwrote the value A's already-finished resolution read.
			expect( await resolve.getVoices( 'en_US' ) ).toEqual(
				VOICES.en_US
			);
			expect( await resolve.getVoices( 'fr_FR' ) ).toEqual(
				VOICES.fr_FR
			);

			// No refetch on re-read — resolution for [ 'en_US' ] is finished.
			const select = registry.select( STORE );
			expect( select.getVoices( 'en_US' ) ).toEqual( VOICES.en_US );
			expect( select.getVoices( 'fr_FR' ) ).toEqual( VOICES.fr_FR );
		} );

		it( 'resolves each language once and reuses the cached result', async () => {
			const resolve = registry.resolveSelect( STORE );
			await resolve.getVoices( 'en_US' );
			await resolve.getVoices( 'en_US' );
			await resolve.getVoices( 'fr_FR' );

			// en_US fetched once (second call is a cache hit), fr_FR once.
			expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		} );

		it( 'returns an empty list for a language that has not been fetched', () => {
			expect( registry.select( STORE ).getVoices( 'de_DE' ) ).toEqual(
				[]
			);
		} );
	} );

	describe( 'getProject / getVideoSizes', () => {
		it( 'keeps each project id in its own slot', async () => {
			const resolve = registry.resolveSelect( STORE );
			await resolve.getProject( '11111' );
			await resolve.getProject( '22222' );
			await resolve.getVideoSizes( '11111' );
			await resolve.getVideoSizes( '22222' );

			const select = registry.select( STORE );
			expect( select.getProject( '11111' ) ).toEqual( PROJECT[ 11111 ] );
			expect( select.getProject( '22222' ) ).toEqual( PROJECT[ 22222 ] );
			expect( select.getVideoSizes( '11111' ) ).toEqual(
				VIDEO_SIZES[ 11111 ]
			);
			expect( select.getVideoSizes( '22222' ) ).toEqual(
				VIDEO_SIZES[ 22222 ]
			);
		} );

		it( 'defaults to {} / [] and skips the fetch for a falsy id', async () => {
			const resolve = registry.resolveSelect( STORE );
			expect( await resolve.getProject( '' ) ).toEqual( {} );
			expect( await resolve.getVideoSizes( '' ) ).toEqual( [] );
			expect( apiFetch ).not.toHaveBeenCalled();
		} );
	} );
} );
