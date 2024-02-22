context( 'Classic Editor: Select Voice', () => {
  const postTypes = require( '../../../fixtures/post-types.json' )

  beforeEach( () => {
    cy.task( 'reset' )
    cy.login()
    cy.savePluginSettings()
    cy.activatePlugin( 'classic-editor' )
  } )

  after( () => {
    cy.task( 'reset' )
  } )

  // Only test priority post types
  postTypes.filter( x => x.priority ).forEach( postType => {
    it( `shows no "Language" component for a ${postType.name} if languages are not selected`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      // Language and Voice should not exist
      cy.get( 'select#beyondwords_language_id' ).should( 'not.exist' )
      cy.get( 'select#beyondwords_voice_id' ).should( 'not.exist' )
    })

    it( `can set a Voice for a ${postType.name} if languages are selected`, () => {
      cy.setLanguagesInPluginSettings();

      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      // Assert we have the expected Languages
      cy.get( 'select#beyondwords_language_id' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.deep.eq( ["Project default", "Language 1", "Language 2"] )
      })

      // Select a Language
      cy.get( 'select#beyondwords_language_id' ).select( 'Language 1' ).wait( 1000 )

      // Assert we have the expected Voices
      cy.get( 'select#beyondwords_voice_id' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.deep.eq( ["", "Voice 1-a", "Voice 1-b", "Voice 1-c"] )
      })

      // Select a Voice
      cy.get( 'select#beyondwords_voice_id' ).select( 'Voice 1-c' )

      // Select another Language
      cy.get( 'select#beyondwords_language_id' ).select( 'Language 2' ).wait( 1000 )

      // Assert we have the expected Voices
      cy.get( 'select#beyondwords_voice_id' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.deep.eq( ["", "Voice 2-a", "Voice 2-b", "Voice 2-c"] )
      })

      // Select a Voice
      cy.get( 'select#beyondwords_voice_id' ).select( 'Voice 2-b' )

      cy.classicSetPostTitle( `I can select a custom Voice for a ${postType.name}` )

      if ( postType.preselect ) {
        cy.get( 'input#beyondwords_generate_audio' ).should( 'be.checked' )
      } else {
        cy.get( 'input#beyondwords_generate_audio' ).should( 'not.be.checked' )
        cy.get( 'input#beyondwords_generate_audio' ).check()
      }

      cy.contains( 'input[type="submit"]', 'Publish' ).click().wait( 1000 )

      // Check Language/Voice has been saved by refreshing the page
      cy.get( 'select#beyondwords_language_id' ).find( 'option:selected' ).should( 'have.text', 'Language 2' )
      cy.get( 'select#beyondwords_voice_id' ).find( 'option:selected' ).should( 'have.text', 'Voice 2-b' )
    } )
  } )

  postTypes.filter( x => ! x.supported ).forEach( postType => {
    it( `has no Voice component for a ${postType.name}`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      // Language and Voice should not be visible
      cy.get( 'select#beyondwords_language_id' ).should( 'not.exist' )
      cy.get( 'select#beyondwords_voice_id' ).should( 'not.exist' )
    } )
  } )
} )
