context( 'Block Editor: Select Voice', () => {
  const postTypes = require( '../../../fixtures/post-types.json' )

  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
  } )

  beforeEach( () => {
    cy.login()
  } )

  // Only test priority post types
  postTypes.filter( x => x.priority ).forEach( postType => {
    it( `shows no "Language" component for a ${postType.name} if languages are not selected`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      // Language and Voice should not exist
      cy.get( 'label' ).contains( 'Language' ).should( 'not.exist' )
      cy.get( 'label' ).contains( 'Voice' ).should( 'not.exist' )
    })

    it( `can set a Voice for a ${postType.name} if languages are selected`, () => {
      cy.setLanguagesInPluginSettings();

      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      cy.closeWelcomeToBlockEditorTips()

      cy.openBeyondwordsEditorPanel()

      // Assert we have the expected Voices
      cy.getBlockEditorSelect( 'Language' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.deep.eq( ["Project default", "Language 1", "Language 2"] )
      })

      // Select a Language
      cy.getBlockEditorSelect( 'Language' ).select( 'Language 1' ).wait( 2000 )

      // Assert we have the expected Voices
      cy.getBlockEditorSelect( 'Voice' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.deep.eq( ["", "Voice 1", "Voice 2", "Voice 3"] )
      })

      // Select a Voice
      cy.getBlockEditorSelect( 'Voice' ).select( 'Voice 3' )

      // Select another Language
      cy.getBlockEditorSelect( 'Language' ).select( 'Language 2' ).wait( 2000 )

      // Assert we have the expected Voices
      cy.getBlockEditorSelect( 'Voice' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.deep.eq( ["", "Voice 1", "Voice 2", "Voice 3"] )
      })

      // Select a Voice
      cy.getBlockEditorSelect( 'Voice' ).select( 'Voice 2' )

      cy.setPostTitle( `I can select a custom Voice for a ${postType.name}` )

      cy.checkGenerateAudio( postType )

      // TODO check Language/Voice in Sidebar

      // TODO check Language/Voice in Prepublish panel

      cy.publishWithConfirmation( true )

      // Check Language/Voice has been saved by refreshing the page
      cy.reload()
      cy.openBeyondwordsEditorPanel()
      cy.getBlockEditorSelect( 'Language' ).find( 'option:selected' ).should( 'have.text', 'Language 2' )
      cy.getBlockEditorSelect( 'Voice' ).find( 'option:selected' ).should( 'have.text', 'Voice 2' )
    } )
  } )
} )
