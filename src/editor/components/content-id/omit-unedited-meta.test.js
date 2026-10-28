/* global describe, it, expect */

/**
 * Internal dependencies
 */
import { uneditedServerMetaKeys } from './omit-unedited-meta';

const ID = '9279c9e0-e0b5-4789-9040-f44478ed3e9e';

describe( 'uneditedServerMetaKeys', () => {
	it( 'lists server-written keys the author has not changed', () => {
		const meta = {
			beyondwords_content_id: '',
			beyondwords_project_id: '',
			beyondwords_preview_token: '',
			beyondwords_generate_audio: '0',
		};
		const persisted = {
			beyondwords_content_id: '',
			beyondwords_project_id: '',
			beyondwords_preview_token: '',
			beyondwords_generate_audio: '1',
		};

		expect( uneditedServerMetaKeys( meta, persisted ) ).toEqual( [
			'beyondwords_content_id',
			'beyondwords_project_id',
			'beyondwords_preview_token',
		] );
	} );

	it( 'leaves out keys the author has changed', () => {
		expect(
			uneditedServerMetaKeys(
				{ beyondwords_content_id: ID, beyondwords_project_id: '1' },
				{ beyondwords_content_id: '', beyondwords_project_id: '1' }
			)
		).toEqual( [ 'beyondwords_project_id' ] );
	} );

	it( 'leaves out a cleared Content ID', () => {
		expect(
			uneditedServerMetaKeys(
				{ beyondwords_content_id: '' },
				{ beyondwords_content_id: ID }
			)
		).toEqual( [] );
	} );

	it( 'returns nothing without meta', () => {
		expect( uneditedServerMetaKeys( undefined, {} ) ).toEqual( [] );
		expect( uneditedServerMetaKeys( {}, undefined ) ).toEqual( [] );
	} );
} );
