/* global Cypress, cy, before, beforeEach, context, it */

context( 'Settings > Authentication', () => {
	before( () => {
		// This test file requires a fresh database WITHOUT credentials
		// to properly test the credential entry flow
		cy.task( 'setupFreshDatabase' );
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'prompts for API credentials and hides tabs until they are validated', () => {
		cy.env( [ 'apiKey' ] ).then( ( { apiKey } ) => {
			cy.visit( '/wp-admin' );

			cy.showsPluginSettingsNotice();
			cy.getPluginSettingsNoticeLink().click();
			cy.showsOnlyAuthenticationSettingsTab();

			// Empty API Key & Project ID
			cy.get( 'input[name="beyondwords_api_key"]' ).should(
				'have.value',
				''
			);
			cy.get( 'input[name="beyondwords_project_id"]' ).should(
				'have.value',
				''
			);
			cy.get( 'input[type="submit"]' ).click();
			cy.showsPluginSettingsNotice();
			cy.showsOnlyAuthenticationSettingsTab();

			// Empty API Key
			cy.get( 'input[name="beyondwords_api_key"]' ).clear();
			cy.get( 'input[name="beyondwords_project_id"]' )
				.clear()
				.type( Cypress.expose( 'projectId' ) );
			cy.get( 'input[type="submit"]' ).click();
			cy.showsPluginSettingsNotice();
			cy.showsOnlyAuthenticationSettingsTab();

			// Empty Project ID
			cy.get( 'input[name="beyondwords_api_key"]' )
				.clear()
				.type( apiKey );
			cy.get( 'input[name="beyondwords_project_id"]' ).clear();
			cy.get( 'input[type="submit"]' ).click();
			cy.showsPluginSettingsNotice();
			cy.showsOnlyAuthenticationSettingsTab();

			// Invalid creds
			cy.get( 'input[name="beyondwords_api_key"]' )
				.clear()
				.type( apiKey );

			// Project 401 triggers a 401 response in the mock API
			cy.get( 'input[name="beyondwords_project_id"]' )
				.clear()
				.type( '401' );
			cy.get( 'input[type="submit"]' ).click();
			cy.showsInvalidApiCredsNotice();
			cy.showsOnlyAuthenticationSettingsTab();

			// Valid API Key & Project ID
			cy.get( 'input[name="beyondwords_api_key"]' )
				.clear()
				.type( apiKey );
			cy.get( 'input[name="beyondwords_project_id"]' )
				.clear()
				.type( Cypress.expose( 'projectId' ) );
			cy.get( 'input[type="submit"]' ).click();
			cy.get( '.notice.notice-info' ).should( 'not.exist' );
			cy.showsAllSettingsTabs();

			// No notices
			cy.get( '.notice-error' ).should( 'not.exist' );

			cy.contains(
				'#setting-error-settings_updated',
				'Settings saved.'
			);

			cy.get( 'input[name="beyondwords_api_key"]' ).should(
				'have.value',
				apiKey
			);
			cy.get( 'input[name="beyondwords_project_id"]' ).should(
				'have.value',
				Cypress.expose( 'projectId' )
			);

			cy.visit( '/wp-admin/options.php' );
			cy.get( '#beyondwords_api_key' ).should( 'have.value', apiKey );
			cy.get( '#beyondwords_project_id' ).should(
				'have.value',
				Cypress.expose( 'projectId' )
			);
			cy.get( '#beyondwords_valid_api_connection' ).should( 'exist' );
			cy.get( '#beyondwords_version' ).should( 'exist' );
		} );
	} );
} );
