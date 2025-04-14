context( 'Classic Editor: Select Voice', () => {
  const postTypes = require( '../../../fixtures/post-types.json' )

  before( () => {
    cy.task( 'reset' )
    cy.login()
    cy.saveStandardPluginSettings()
    cy.activatePlugin( 'classic-editor' )
  } )

  beforeEach( () => {
    cy.login()
  } )

  after( () => {
    cy.deactivatePlugin( 'classic-editor' )
  } )

  // Only test priority post types
  postTypes.filter( x => x.priority ).forEach( postType => {
    it( `can set a Voice for a ${postType.name} if languages are selected`, () => {
      cy.visit( `/wp-admin/post-new.php?post_type=${postType.slug}` ).wait( 500 )

      // Assert we have the expected Languages
      cy.get( 'select#beyondwords_language_code' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.have.length(148)
        expect(values).to.include("English (American)")
        expect(values).to.include("English (British)")
        expect(values).to.include("Welsh (Welsh)")
      })

      // Select a Language
      cy.get( 'select#beyondwords_language_code' ).select( 'English (American)' ).wait( 1000 )

      // Assert we have the expected Voices
      cy.get( 'select#beyondwords_voice_id' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.deep.eq( ["Ada (Multilingual)", "Ava (Multilingual)", "Ollie (Multilingual)"] )
      })

      // Select a Voice
      cy.get( 'select#beyondwords_voice_id' ).select( 'Ollie (Multilingual)' )

      // Select another Language
      cy.get( 'select#beyondwords_language_code' ).select( 'English (British)' ).wait( 1000 )

      // Assert we have the expected Voices
      cy.get( 'select#beyondwords_voice_id' ).find( 'option' ).should( $els => {
        const values = [ ...$els ].map( el => el.innerText.trim() )
        expect(values).to.deep.eq( ["Ada (Multilingual)", "Ava (Multilingual)", "Ollie (Multilingual)"] )
      })

      // Select a Voice
      cy.get( 'select#beyondwords_voice_id' ).select( 'Ada (Multilingual)' )

      cy.classicSetPostTitle( `I can select a custom Voice for a ${postType.name}` )

      if ( postType.preselect ) {
        cy.get( 'input#beyondwords_generate_audio' ).should( 'be.checked' )
      } else {
        cy.get( 'input#beyondwords_generate_audio' ).should( 'not.be.checked' )
        cy.get( 'input#beyondwords_generate_audio' ).check()
      }

      cy.contains( 'input[type="submit"]', 'Publish' ).click().wait( 1000 )

      // Check Language/Voice has been saved by refreshing the page
      cy.get( 'select#beyondwords_language_code' ).find( 'option:selected' ).should( 'have.text', 'English (British)' )
      cy.get( 'select#beyondwords_voice_id' ).find( 'option:selected' ).should( 'have.text', 'Ada (Multilingual)' )
    } )
  } )
} )
