/* global describe, it, expect */

/**
 * Internal dependencies
 */
import { canPlayAudio } from './helpers';

// A fake `select( 'core/editor' )` exposing getEditedPostAttribute over a given
// post status + meta, matching how canPlayAudio reads the store.
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

describe( 'canPlayAudio', () => {
	it( 'is false with no project id and no content id', () => {
		expect( canPlayAudio( makeSelect() ) ).toBe( false );
	} );

	describe( 'client-side integration', () => {
		const clientSide = ( meta ) =>
			makeSelect( {
				meta: {
					beyondwords_integration_method: 'client-side',
					...meta,
				},
			} );

		it( 'renders with just a project id (no content id needed)', () => {
			expect(
				canPlayAudio( clientSide( { beyondwords_project_id: 123 } ) )
			).toBe( true );
		} );

		it( 'does not render without a project id', () => {
			expect( canPlayAudio( clientSide( {} ) ) ).toBe( false );
		} );
	} );

	describe( 'REST API integration', () => {
		const restApi = ( meta ) => makeSelect( { meta } );

		it( 'renders with both a project id and a content id', () => {
			expect(
				canPlayAudio(
					restApi( {
						beyondwords_project_id: 123,
						beyondwords_content_id: 456,
					} )
				)
			).toBe( true );
		} );

		it( 'does not render with a project id but no content id', () => {
			expect(
				canPlayAudio( restApi( { beyondwords_project_id: 123 } ) )
			).toBe( false );
		} );

		it( 'does not render with a content id but no project id', () => {
			// The legacy/partial-meta bug case: a content id exists but
			// beyondwords_project_id is missing, so no player would render.
			expect(
				canPlayAudio( restApi( { beyondwords_content_id: 456 } ) )
			).toBe( false );
		} );

		it( 'honours legacy beyondwords_podcast_id as the content id', () => {
			expect(
				canPlayAudio(
					restApi( {
						beyondwords_project_id: 123,
						beyondwords_podcast_id: 456,
					} )
				)
			).toBe( true );
		} );

		it( 'honours legacy speechkit_podcast_id as the content id', () => {
			expect(
				canPlayAudio(
					restApi( {
						beyondwords_project_id: 123,
						speechkit_podcast_id: 456,
					} )
				)
			).toBe( true );
		} );
	} );

	describe( 'pending review', () => {
		it( 'never renders for a pending post with full REST content', () => {
			// The empty-panel bug case: full content but status === 'pending'
			// means PlayAudio renders nothing, so the panel must stay hidden.
			expect(
				canPlayAudio(
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

		it( 'never renders for a pending client-side post', () => {
			expect(
				canPlayAudio(
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

	it( 'tolerates missing meta', () => {
		const select = () => ( {
			getEditedPostAttribute: ( attr ) =>
				attr === 'status' ? 'publish' : undefined,
		} );
		expect( canPlayAudio( select ) ).toBe( false );
	} );
} );
