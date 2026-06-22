/* global describe, it, expect */

/**
 * Internal dependencies
 */
import { selectHasPlayAudioAction } from './hooks';

// A fake `select( 'core/editor' )` exposing getEditedPostAttribute over a given
// post status + meta, matching how selectHasPlayAudioAction reads the store.
const makeSelect =
	( { status = 'publish', meta = {} } = {} ) =>
	() => ( {
		getEditedPostAttribute: ( attr ) => {
			if ( attr === 'status' ) {
				return status;
			}
			if ( attr === 'meta' ) {
				return meta;
			}
			return undefined;
		},
	} );

describe( 'selectHasPlayAudioAction', () => {
	it( 'is false with no project id and no content id', () => {
		expect( selectHasPlayAudioAction( makeSelect() ) ).toBe( false );
	} );

	describe( 'client-side integration', () => {
		const clientSide = ( meta ) =>
			makeSelect( {
				meta: {
					beyondwords_integration_method: 'client-side',
					...meta,
				},
			} );

		it( 'loads with just a project id (no content id needed)', () => {
			expect(
				selectHasPlayAudioAction(
					clientSide( { beyondwords_project_id: 123 } )
				)
			).toBe( true );
		} );

		it( 'does not load without a project id', () => {
			expect( selectHasPlayAudioAction( clientSide( {} ) ) ).toBe(
				false
			);
		} );
	} );

	describe( 'REST API integration', () => {
		const restApi = ( meta ) => makeSelect( { meta } );

		it( 'loads with both a project id and a content id', () => {
			expect(
				selectHasPlayAudioAction(
					restApi( {
						beyondwords_project_id: 123,
						beyondwords_content_id: 456,
					} )
				)
			).toBe( true );
		} );

		it( 'does not load with a project id but no content id', () => {
			expect(
				selectHasPlayAudioAction(
					restApi( { beyondwords_project_id: 123 } )
				)
			).toBe( false );
		} );

		it( 'does not load with a content id but no project id', () => {
			// The legacy/partial-meta bug case: a content id exists but
			// beyondwords_project_id is missing, so no player would load.
			expect(
				selectHasPlayAudioAction(
					restApi( { beyondwords_content_id: 456 } )
				)
			).toBe( false );
		} );

		it( 'honours legacy beyondwords_podcast_id as the content id', () => {
			expect(
				selectHasPlayAudioAction(
					restApi( {
						beyondwords_project_id: 123,
						beyondwords_podcast_id: 456,
					} )
				)
			).toBe( true );
		} );

		it( 'honours legacy speechkit_podcast_id as the content id', () => {
			expect(
				selectHasPlayAudioAction(
					restApi( {
						beyondwords_project_id: 123,
						speechkit_podcast_id: 456,
					} )
				)
			).toBe( true );
		} );
	} );

	describe( 'pending review', () => {
		it( 'never loads for a pending post with full REST content', () => {
			// Pending status means the player can't load, so the Preview panel
			// shows the placeholder instead of the live player.
			expect(
				selectHasPlayAudioAction(
					makeSelect( {
						status: 'pending',
						meta: {
							beyondwords_project_id: 123,
							beyondwords_content_id: 456,
						},
					} )
				)
			).toBe( false );
		} );

		it( 'never loads for a pending client-side post', () => {
			expect(
				selectHasPlayAudioAction(
					makeSelect( {
						status: 'pending',
						meta: {
							beyondwords_integration_method: 'client-side',
							beyondwords_project_id: 123,
						},
					} )
				)
			).toBe( false );
		} );
	} );
} );
