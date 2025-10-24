/* global Cypress, cy, beforeEach, context, it */

context( 'Settings > Credentials', () => {
	beforeEach( () => {
		cy.login();
		// Fast cleanup of test posts (100-500ms vs 5-10s full reset)
		cy.cleanupTestPosts();
	} );

	it( 'prompts for API credentials and hides tabs until they are validated', () => {
		cy.visit( '/wp-admin' );

		cy.showsPluginSettingsNotice();
		cy.getPluginSettingsNoticeLink().click();
		cy.showsOnlyCredentialsSettingsTab();

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
		cy.showsOnlyCredentialsSettingsTab();

		// Empty API Key
		cy.get( 'input[name="beyondwords_api_key"]' ).clear();
		cy.get( 'input[name="beyondwords_project_id"]' )
			.clear()
			.type( Cypress.env( 'projectId' ) );
		cy.get( 'input[type="submit"]' ).click();
		cy.showsPluginSettingsNotice();
		cy.showsOnlyCredentialsSettingsTab();

		// Empty Project ID
		cy.get( 'input[name="beyondwords_api_key"]' )
			.clear()
			.type( Cypress.env( 'apiKey' ) );
		cy.get( 'input[name="beyondwords_project_id"]' ).clear();
		cy.get( 'input[type="submit"]' ).click();
		cy.showsPluginSettingsNotice();
		cy.showsOnlyCredentialsSettingsTab();

		// Invalid creds
		cy.get( 'input[name="beyondwords_api_key"]' )
			.clear()
			.type( Cypress.env( 'apiKey' ) );

		// Project 401 triggers a 403 response in Mockoon
		cy.get( 'input[name="beyondwords_project_id"]' ).clear().type( '401' );
		cy.get( 'input[type="submit"]' ).click();
		cy.showsInvalidApiCredsNotice();
		cy.showsOnlyCredentialsSettingsTab();

		// Valid API Key & Project ID
		cy.get( 'input[name="beyondwords_api_key"]' )
			.clear()
			.type( Cypress.env( 'apiKey' ) );
		cy.get( 'input[name="beyondwords_project_id"]' )
			.clear()
			.type( Cypress.env( 'projectId' ) );
		cy.get( 'input[type="submit"]' ).click();
		cy.get( '.notice.notice-info' ).should( 'not.exist' );
		cy.showsAllSettingsTabs();

		// No notices
		cy.get( '.notice-error' ).should( 'not.exist' );

		cy.contains( '#setting-error-settings_updated', 'Settings saved.' );

		cy.get( 'input[name="beyondwords_api_key"]' ).should(
			'have.value',
			Cypress.env( 'apiKey' )
		);
		cy.get( 'input[name="beyondwords_project_id"]' ).should(
			'have.value',
			Cypress.env( 'projectId' )
		);

		cy.visit( '/wp-admin/options.php' );
		cy.get( '#beyondwords_api_key' ).should(
			'have.value',
			Cypress.env( 'apiKey' )
		);
		cy.get( '#beyondwords_project_id' ).should(
			'have.value',
			Cypress.env( 'projectId' )
		);
		cy.get( '#beyondwords_valid_api_connection' ).should( 'exist' );
		cy.get( '#beyondwords_version' ).should( 'exist' );
	} );
} );
