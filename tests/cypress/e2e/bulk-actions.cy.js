context( 'Bulk Actions', () => {
  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  const postTypes = require( '../../../tests/fixtures/post-types.json' )

  postTypes.filter( x => x.priority ).forEach( postType => {
    it( `does bulk actions for for ${postType.name}s`, () => {
      cy.createPostWithoutAudio(`Bulk actions for ${postType.name}s (1)`, postType);
      cy.createPostWithoutAudio(`Bulk actions for ${postType.name}s (2)`, postType);
      cy.createPostWithoutAudio(`Bulk actions for ${postType.name}s (3)`, postType);

      cy.visit( `/wp-admin/edit.php?post_type=${postType.slug}&orderby=date&order=desc` ).wait( 500 )

      cy.get( 'tbody tr' ).eq( 0 )
        .within( () => {
          // See a [—] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords' ).contains( '—' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      cy.get( 'tbody tr' ).eq( 1 )
        .within( () => {
          // See a [—] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords' ).contains( '—' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      cy.get( 'tbody tr' ).eq( 2 )
        .within( () => {
          // See a [—] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords' ).contains( '—' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      // "Bulk actions" > "Generate audio" > "Apply"
      cy.get( '#bulk-action-selector-top' ).select( 'Delete audio' )
      cy.get( '#doaction' ).click().wait( 500 )

      cy.get('div.notice.notice-error').contains('None of the selected posts had valid BeyondWords audio data.');

      cy.get( 'tbody tr' ).eq( 0 )
        .within( () => {
          // See a [—] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords' ).contains( '—' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      cy.get( 'tbody tr' ).eq( 1 )
        .within( () => {
          // See a [—] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords' ).contains( '—' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      cy.get( 'tbody tr' ).eq( 2 )
        .within( () => {
          // See a [—] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords' ).contains( '—' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      // "Bulk actions" > "Generate audio" > "Apply"
      cy.get( '#bulk-action-selector-top' ).select( 'Generate audio' )
      cy.get( '#doaction' ).click().wait( 500 )

      cy.get('div.notice.notice-info').contains('Audio was requested for 3 posts.');
      cy.get('div.notice.notice-error').should('not.be.visible');

      cy.get( 'tbody tr' ).eq( 0 )
        .within( () => {
          // See a [tick] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
          // todo save URL and visit it to check player exists
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      cy.get( 'tbody tr' ).eq( 1 )
        .within( () => {
          // See a [tick] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
          // todo save URL and visit it to check player exists
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      cy.get( 'tbody tr' ).eq( 2 )
        .within( () => {
          // See a [tick] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
          // todo save URL and visit it to check player exists
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      // "Bulk actions" > "Delete audio" > "Apply"
      cy.get( '#bulk-action-selector-top' ).select( 'Delete audio' )
      cy.get( '#doaction' ).click()

      cy.get('div.notice.notice-info').contains('Audio was deleted for 3 posts.');
      cy.get('div.notice.notice-error').should('not.be.visible');

      cy.get( 'tbody tr' ).eq( 2 )
        .within( () => {
          // See a [—] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords' ).contains( '—' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      // "Bulk actions" > "Generate audio" > "Apply"
      cy.get( '#bulk-action-selector-top' ).select( 'Generate audio' )
      cy.get( '#doaction' ).click().wait( 500 )

      cy.get('div.notice.notice-info').contains('Audio was requested for 1 post.');
      cy.get('div.notice.notice-error').should('not.be.visible');

      cy.get( 'tbody tr' ).eq( 0 )
        .within( () => {
          // See a [—] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords' ).contains( '—' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      cy.get( 'tbody tr' ).eq( 1 )
        .within( () => {
          // See a [—] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords' ).contains( '—' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      cy.get( 'tbody tr' ).eq( 2 )
        .within( () => {
          // See a [tick] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      // "Bulk actions" > "Generate audio" > "Apply"
      cy.get( '#bulk-action-selector-top' ).select( 'Generate audio' )
      cy.get( '#doaction' ).click().wait( 500 )

      cy.get('div.notice.notice-info').contains('Audio was requested for 3 posts.');
      cy.get('div.notice.notice-error').should('not.be.visible');

      cy.get( 'tbody tr' ).eq( 1 )
        .within( () => {
          // See a [tick] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      // "Bulk actions" > "Delete audio" > "Apply"
      cy.get( '#bulk-action-selector-top' ).select( 'Delete audio' )
      cy.get( '#doaction' ).click().wait( 500 )

      cy.get('div.notice.notice-info').contains('Audio was deleted for 1 post.');
      cy.get('div.notice.notice-error').should('not.be.visible');

      cy.get( 'tbody tr' ).eq( 0 )
        .within( () => {
          // See a [tick] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      cy.get( 'tbody tr' ).eq( 1 )
        .within( () => {
          // See a [—] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords' ).contains( '—' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      cy.get( 'tbody tr' ).eq( 2 )
        .within( () => {
          // See a [tick] in the BeyondWords column
          cy.get( 'td.beyondwords.column-beyondwords > span.dashicons.dashicons-yes' )
          // Check the checkbox
          cy.get( 'input[type="checkbox"][name="post[]"]' ).check()
        } )

      // "Bulk actions" > "Delete audio" > "Apply"
      cy.get( '#bulk-action-selector-top' ).select( 'Delete audio' )
      cy.get( '#doaction' ).click().wait( 500 )

      cy.get('div.notice.notice-info').contains('Audio was deleted for 2 posts.');
      cy.get('div.notice.notice-error').should('not.be.visible');
    } )
  } )
} )
