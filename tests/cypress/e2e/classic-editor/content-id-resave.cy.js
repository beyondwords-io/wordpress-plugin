/**
 * @group classic-editor
 * @covers src/editor/components/content-id/
 * @covers src/editor/components/generate-audio/
 * @covers src/editor/classic/class-metabox.php
 * @covers src/post/class-sync.php
 */

/* global Cypress, cy, before, beforeEach, after, context, it */

const postTypes = require( '../../../../tests/fixtures/post-types.json' );

// Differs from the mock API's fixed create ID, so a recreate can't pass as kept.
const EXISTING_ID = 'b3f1c2d4-5e6f-4a7b-8c9d-0e1f2a3b4c5d';
const OTHER_ID = 'c4a2d3e5-6f70-4b8c-9d0e-1f2a3b4c5d6e';

const createdId = () => Cypress.expose( 'contentId' );

const save = ( generateAudio ) => {
	cy.get( 'input#beyondwords_generate_audio' )[
		generateAudio ? 'check' : 'uncheck'
	]();
	cy.window().then( ( win ) => {
		win.beyondwordsBeforeSave = true;
	} );
	cy.get( '#publish' ).click();

	// The previous save's notice is still on screen until the new page loads.
	cy.window().should( 'not.have.property', 'beyondwordsBeforeSave' );
	cy.get( '#message.updated' ).should( 'exist' );
};

const expectContentId = ( postId, value ) => {
	cy.task( 'getPostMeta', {
		postId,
		metaKey: 'beyondwords_content_id',
	} ).should( 'equal', value );
};

const expectField = ( value ) => {
	cy.get( '#beyondwords_content_id', { timeout: 15000 } ).should(
		'have.value',
		value
	);
};

const expectScheduled = ( postId, scheduled ) => {
	cy.task( 'isAudioGenerationScheduled', postId ).should(
		'equal',
		scheduled
	);
};

const plantContentId = ( postId, contentId ) =>
	cy
		.task( 'setPostMeta', {
			postId,
			metaKey: 'beyondwords_project_id',
			metaValue: Cypress.expose( 'projectId' ),
		} )
		.task( 'setPostMeta', {
			postId,
			metaKey: 'beyondwords_content_id',
			metaValue: contentId,
		} );

const editPost = ( postId ) =>
	cy.visit( `/wp-admin/post.php?post=${ postId }&action=edit` );

context( 'Classic Editor: Content ID across resaves', () => {
	before( () => {
		cy.task( 'activatePlugin', 'classic-editor' );
	} );

	beforeEach( () => {
		cy.login();

		// Terminal status, so the metabox never loads the player SDK.
		cy.intercept( 'GET', '**/beyondwords/v1/projects/*/content/*', {
			statusCode: 200,
			body: { status: 'error' },
		} );
	} );

	after( () => {
		cy.task( 'deactivatePlugin', 'classic-editor' );
	} );

	context( 'Synchronous generation (non-VIP)', () => {
		postTypes
			.filter( ( x ) => x.priority )
			.forEach( ( postType ) => {
				it( `${ postType.name }: publish with audio, then resave with Generate audio toggled`, () => {
					cy.createTestPost( {
						title: `Cypress Test: sync publish ${ postType.slug }`,
						status: 'draft',
						postType: postType.slug,
					} ).then( ( postId ) => {
						editPost( postId );
						save( true );

						expectScheduled( postId, false );
						expectContentId( postId, createdId() );
						expectField( createdId() );

						[ false, true, false ].forEach( ( generateAudio ) => {
							save( generateAudio );
							expectContentId( postId, createdId() );
							expectField( createdId() );
						} );
					} );
				} );

				it( `${ postType.name }: an existing Content ID survives resaves`, () => {
					cy.createTestPost( {
						title: `Cypress Test: sync existing ${ postType.slug }`,
						postType: postType.slug,
					} ).then( ( postId ) => {
						plantContentId( postId, EXISTING_ID );
						editPost( postId );
						expectField( EXISTING_ID );

						[ false, true, false ].forEach( ( generateAudio ) => {
							save( generateAudio );
							expectContentId( postId, EXISTING_ID );
							expectField( EXISTING_ID );
						} );
					} );
				} );

				it( `${ postType.name }: no Content ID stays unlinked until Generate audio is ticked`, () => {
					cy.createTestPost( {
						title: `Cypress Test: sync none ${ postType.slug }`,
						postType: postType.slug,
					} ).then( ( postId ) => {
						editPost( postId );

						save( false );
						save( false );
						expectContentId( postId, '' );
						expectField( '' );

						save( true );
						expectContentId( postId, createdId() );
						expectField( createdId() );
					} );
				} );

				it( `${ postType.name }: resave from a form opened before the Content ID existed keeps it`, () => {
					cy.createTestPost( {
						title: `Cypress Test: sync stale ${ postType.slug }`,
						postType: postType.slug,
					} ).then( ( postId ) => {
						editPost( postId );
						expectField( '' );

						plantContentId( postId, EXISTING_ID );

						save( false );
						expectContentId( postId, EXISTING_ID );
						expectField( EXISTING_ID );
					} );
				} );

				it( `${ postType.name }: the author can change or clear the Content ID`, () => {
					cy.createTestPost( {
						title: `Cypress Test: sync edit ${ postType.slug }`,
						postType: postType.slug,
					} ).then( ( postId ) => {
						plantContentId( postId, EXISTING_ID );
						editPost( postId );

						cy.get( '#beyondwords_content_id' )
							.clear()
							.type( OTHER_ID );
						save( false );
						expectContentId( postId, OTHER_ID );
						expectField( OTHER_ID );

						cy.get( '#beyondwords_content_id' ).clear();
						save( false );
						expectContentId( postId, '' );
						expectField( '' );
					} );
				} );
			} );
	} );

	context( 'Deferred generation (VIP)', () => {
		before( () => {
			cy.task( 'activatePlugin', 'beyondwords-emulate-vip' );
		} );

		after( () => {
			cy.task( 'deactivatePlugin', 'beyondwords-emulate-vip' );
		} );

		postTypes
			.filter( ( x ) => x.priority )
			.forEach( ( postType ) => {
				it( `${ postType.name }: publish with audio, the job runs, then resave with Generate audio toggled`, () => {
					cy.createTestPost( {
						title: `Cypress Test: vip publish ${ postType.slug }`,
						status: 'draft',
						postType: postType.slug,
					} ).then( ( postId ) => {
						editPost( postId );
						save( true );

						expectScheduled( postId, true );
						expectContentId( postId, '' );
						cy.get( '#beyondwords-metabox-player' ).should(
							'have.attr',
							'data-await-content',
							'1'
						);

						cy.task( 'runAudioGeneration' );
						expectContentId( postId, createdId() );
						expectField( createdId() );

						save( false );
						expectContentId( postId, createdId() );

						save( true );
						expectScheduled( postId, true );
						cy.task( 'runAudioGeneration' );
						expectContentId( postId, createdId() );

						save( false );
						expectContentId( postId, createdId() );
						expectField( createdId() );
					} );
				} );

				it( `${ postType.name }: resave before the metabox sees the job's Content ID keeps it`, () => {
					cy.createTestPost( {
						title: `Cypress Test: vip race ${ postType.slug }`,
						status: 'draft',
						postType: postType.slug,
					} ).then( ( postId ) => {
						// The metabox never learns the ID, so its form stays stale.
						cy.intercept(
							'GET',
							new RegExp(
								`wp(?:/|%2F)v2(?:/|%2F)\\w+(?:/|%2F)${ postId }(?:[?&]|$)`
							),
							{ statusCode: 200, body: { meta: {} } }
						);

						editPost( postId );
						save( true );
						expectScheduled( postId, true );

						cy.task( 'runAudioGeneration' );
						expectField( '' );

						save( false );
						expectContentId( postId, createdId() );
						expectField( createdId() );

						save( false );
						expectContentId( postId, createdId() );
					} );
				} );

				it( `${ postType.name }: an existing Content ID survives resaves and queued jobs`, () => {
					cy.createTestPost( {
						title: `Cypress Test: vip existing ${ postType.slug }`,
						postType: postType.slug,
					} ).then( ( postId ) => {
						plantContentId( postId, EXISTING_ID );
						editPost( postId );

						save( false );
						expectScheduled( postId, false );
						expectContentId( postId, EXISTING_ID );

						save( true );
						expectScheduled( postId, true );
						expectContentId( postId, EXISTING_ID );
						expectField( EXISTING_ID );

						cy.task( 'runAudioGeneration' );
						expectContentId( postId, EXISTING_ID );

						save( false );
						expectContentId( postId, EXISTING_ID );
						expectField( EXISTING_ID );
					} );
				} );

				it( `${ postType.name }: no Content ID stays unlinked until Generate audio is ticked`, () => {
					cy.createTestPost( {
						title: `Cypress Test: vip none ${ postType.slug }`,
						postType: postType.slug,
					} ).then( ( postId ) => {
						editPost( postId );

						save( false );
						save( false );
						expectScheduled( postId, false );
						expectContentId( postId, '' );

						save( true );
						expectScheduled( postId, true );
						expectContentId( postId, '' );

						cy.task( 'runAudioGeneration' );
						expectContentId( postId, createdId() );
						expectField( createdId() );
					} );
				} );
			} );
	} );
} );
