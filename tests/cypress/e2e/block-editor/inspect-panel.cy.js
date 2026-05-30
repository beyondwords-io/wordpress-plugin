/**
 * @group block-editor
 * @covers src/editor/components/inspect-panel/
 */

/* global cy, beforeEach, context, it */

context( 'Block Editor: Inspect Panel', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( 'lists every current meta field (including v7 fields)', () => {
		cy.createPost( {
			title: 'Cypress Test: inspect panel fields',
			postType: { slug: 'post' },
		} );

		cy.openBeyondwordsPluginSidebar();

		const expectedLabels = [
			// pre-v7
			'beyondwords_generate_audio',
			'beyondwords_integration_method',
			'beyondwords_project_id',
			'beyondwords_preview_token',
			'beyondwords_content_id',
			'beyondwords_language_code',
			'beyondwords_language_id',
			'beyondwords_body_voice_id',
			'beyondwords_error_message',
			'beyondwords_disabled',
			'beyondwords_delete_content',
			// v7 additions
			'beyondwords_source',
			'beyondwords_output',
			'beyondwords_script_template_id',
			'beyondwords_video_template_id',
			'beyondwords_video_size',
			'beyondwords_embed',
		];

		// Inspect panel ships collapsed (initialOpen={false}); click to expand.
		cy.get( '.beyondwords-sidebar__inspect' )
			.scrollIntoView()
			.then( ( $el ) => {
				if ( ! $el.hasClass( 'is-opened' ) ) {
					cy.wrap( $el ).find( '.components-panel__body-toggle' ).click();
				}
			} );

		cy.get( '.beyondwords-sidebar__inspect' ).within( () => {
			expectedLabels.forEach( ( label ) => {
				cy.contains( 'label', label ).should( 'exist' );
			} );
		} );
	} );
} );
