/**
 * @group block-editor
 * @covers src/editor/components/content-id/
 * @covers src/editor/components/generate-audio/
 * @covers src/post/class-sync.php
 */

/* global Cypress, cy, before, beforeEach, after, context, it, expect */

const postTypes = require( '../../../../tests/fixtures/post-types.json' );

// Differs from the mock API's fixed create ID, so a recreate can't pass as kept.
const EXISTING_ID = 'b3f1c2d4-5e6f-4a7b-8c9d-0e1f2a3b4c5d';

const createdId = () => Cypress.expose( 'contentId' );

const editorMeta = ( win ) =>
	win.wp.data.select( 'core/editor' ).getEditedPostAttribute( 'meta' );

const setGenerateAudio = ( generateAudio ) => {
	cy.openBeyondwordsEditorPanel();

	// Until settings load the toggle reads unchecked, then Preselect can flip it on.
	cy.window().should( ( win ) => {
		expect(
			win.wp.data
				.select( 'beyondwords/settings' )
				.hasFinishedResolution( 'getSettings', [] )
		).to.equal( true );
	} );

	cy.getGenerateAudioCheckbox().then( ( $input ) => {
		if ( $input.is( ':checked' ) !== generateAudio ) {
			cy.get( '.beyondwords--generate-audio label' ).click();
		}
	} );
	cy.getGenerateAudioCheckbox().should(
		generateAudio ? 'be.checked' : 'not.be.checked'
	);
};

const editPost = ( postId ) => {
	cy.intercept(
		'POST',
		new RegExp( `wp(?:/|%2F)v2(?:/|%2F)\\w+(?:/|%2F)${ postId }(?:[?&]|$)` )
	).as( 'savePost' );
	cy.visitPostEditorById( postId );
};

const save = ( generateAudio ) => {
	setGenerateAudio( generateAudio );

	// A title edit guarantees the post is dirty, so Save is always enabled.
	cy.window().then( ( win ) =>
		win.wp.data
			.dispatch( 'core/editor' )
			.editPost( { title: `Cypress Test: resave ${ Date.now() }` } )
	);

	cy.get( '.editor-post-publish-button' ).click();
	cy.wait( '@savePost' );
	cy.window().should( ( win ) => {
		expect( win.wp.data.select( 'core/editor' ).isSavingPost() ).to.equal(
			false
		);
	} );
};

const publish = () => {
	setGenerateAudio( true );
	cy.publishWithConfirmation();
};

const expectContentId = ( postId, value ) => {
	cy.task( 'getPostMeta', {
		postId,
		metaKey: 'beyondwords_content_id',
	} ).should( 'equal', value );
};

const expectEditorContentId = ( value ) => {
	cy.window().should( ( win ) => {
		expect( editorMeta( win ).beyondwords_content_id ).to.equal( value );
	} );
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

context( 'Block Editor: Content ID across resaves', () => {
	beforeEach( () => {
		cy.login();

		// Terminal status, so the editor never loads the player SDK.
		cy.intercept( 'GET', '**/beyondwords/v1/projects/*/content/*', {
			statusCode: 200,
			body: { status: 'error' },
		} );
	} );

	context( 'Synchronous generation (non-VIP)', () => {
		postTypes
			.filter( ( x ) => x.priority )
			.forEach( ( postType ) => {
				it( `${ postType.name }: publish with audio, then resave with Generate audio toggled`, () => {
					cy.createTestPost( {
						title: `Cypress Test: block sync publish ${ postType.slug }`,
						status: 'draft',
						postType: postType.slug,
					} ).then( ( postId ) => {
						editPost( postId );
						publish();

						expectScheduled( postId, false );
						expectContentId( postId, createdId() );
						expectEditorContentId( createdId() );

						[ false, true, false ].forEach( ( generateAudio ) => {
							save( generateAudio );
							expectContentId( postId, createdId() );
						} );
					} );
				} );

				it( `${ postType.name }: an existing Content ID survives resaves`, () => {
					cy.createTestPost( {
						title: `Cypress Test: block sync existing ${ postType.slug }`,
						postType: postType.slug,
					} ).then( ( postId ) => {
						plantContentId( postId, EXISTING_ID );
						editPost( postId );
						expectEditorContentId( EXISTING_ID );

						[ false, true, false ].forEach( ( generateAudio ) => {
							save( generateAudio );
							expectContentId( postId, EXISTING_ID );
						} );
					} );
				} );

				it( `${ postType.name }: no Content ID stays unlinked until Generate audio is ticked`, () => {
					cy.createTestPost( {
						title: `Cypress Test: block sync none ${ postType.slug }`,
						postType: postType.slug,
					} ).then( ( postId ) => {
						editPost( postId );

						save( false );
						save( false );
						expectContentId( postId, '' );

						save( true );
						expectContentId( postId, createdId() );
						expectEditorContentId( createdId() );

						save( false );
						expectContentId( postId, createdId() );
					} );
				} );

				it( `${ postType.name }: resave from an editor opened before the Content ID existed keeps it`, () => {
					cy.createTestPost( {
						title: `Cypress Test: block sync stale ${ postType.slug }`,
						postType: postType.slug,
					} ).then( ( postId ) => {
						editPost( postId );
						expectEditorContentId( '' );

						plantContentId( postId, EXISTING_ID );

						save( false );
						expectContentId( postId, EXISTING_ID );
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
				it( `${ postType.name }: publish with preselected Generate audio, the job creates audio`, () => {
					cy.createTestPost( {
						title: `Cypress Test: block vip preselect ${ postType.slug }`,
						status: 'draft',
						postType: postType.slug,
					} ).then( ( postId ) => {
						editPost( postId );
						publish();

						expectScheduled( postId, true );
						expectContentId( postId, '' );

						cy.task( 'runAudioGeneration' );
						expectContentId( postId, createdId() );
					} );
				} );

				it( `${ postType.name }: publish with Generate audio ticked by hand, the job runs, then resave keeps the Content ID`, () => {
					cy.createTestPost( {
						title: `Cypress Test: block vip publish ${ postType.slug }`,
						status: 'draft',
						postType: postType.slug,
					} ).then( ( postId ) => {
						editPost( postId );

						// An explicit choice stores the meta, so the job doesn't rely on Preselect.
						setGenerateAudio( false );
						publish();

						expectScheduled( postId, true );
						expectContentId( postId, '' );

						cy.task( 'runAudioGeneration' );
						expectContentId( postId, createdId() );

						save( false );
						expectContentId( postId, createdId() );

						save( true );
						cy.task( 'runAudioGeneration' );
						expectContentId( postId, createdId() );

						save( false );
						expectContentId( postId, createdId() );
					} );
				} );

				it( `${ postType.name }: an existing Content ID survives resaves and queued jobs`, () => {
					cy.createTestPost( {
						title: `Cypress Test: block vip existing ${ postType.slug }`,
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

						cy.task( 'runAudioGeneration' );
						expectContentId( postId, EXISTING_ID );

						save( false );
						expectContentId( postId, EXISTING_ID );
					} );
				} );

				it( `${ postType.name }: no Content ID stays unlinked until Generate audio is ticked`, () => {
					cy.createTestPost( {
						title: `Cypress Test: block vip none ${ postType.slug }`,
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
					} );
				} );
			} );
	} );
} );
