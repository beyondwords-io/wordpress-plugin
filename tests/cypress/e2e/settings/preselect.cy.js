/**
 * @group settings
 * @covers src/settings/class-preselect.php
 * @covers src/settings/preselect.js
 */

/* global cy, beforeEach, context, it */

/**
 * Progressive-disclosure behaviour of the "Preselect 'Generate audio'" field.
 *
 * Tests only toggle the controls (no save), so the stored option is unchanged
 * for other specs. Seeded state: 'post' = mode 'all' ("All" ticked, terms hidden).
 */
context( 'Settings: Preselect Generate audio field', () => {
	beforeEach( () => {
		cy.login();
		cy.visit(
			'/wp-admin/options-general.php?page=beyondwords&tab=preferences'
		);
		cy.dismissPointers();
	} );

	it( 'hides terms behind "All" and reveals them when "All" is unticked', () => {
		cy.get( '[data-post-type="post"]' ).within( () => {
			cy.get( '.beyondwords-setting__preselect--all' )
				.should( 'be.visible' )
				.and( 'be.checked' );
			cy.get( '.beyondwords-setting__preselect--taxonomies' ).should(
				'not.be.visible'
			);

			cy.get( '.beyondwords-setting__preselect--all' ).uncheck();
			cy.get( '.beyondwords-setting__preselect--taxonomies' ).should(
				'be.visible'
			);

			cy.get( '.beyondwords-setting__preselect--all' ).check();
			cy.get( '.beyondwords-setting__preselect--taxonomies' ).should(
				'not.be.visible'
			);
		} );
	} );

	it( 'preserves ticked terms when "All" is re-ticked', () => {
		cy.get( '[data-post-type="post"]' ).within( () => {
			cy.get( '.beyondwords-setting__preselect--all' ).uncheck();

			cy.get(
				'.beyondwords-setting__preselect--taxonomies input[type="checkbox"]'
			)
				.first()
				.check();

			cy.get( '.beyondwords-setting__preselect--all' ).check();
			cy.get( '.beyondwords-setting__preselect--all' ).uncheck();

			cy.get(
				'.beyondwords-setting__preselect--taxonomies input[type="checkbox"]'
			)
				.first()
				.should( 'be.checked' );
		} );
	} );

	it( 'hides options when the post type is disabled and restores "All" when re-enabled', () => {
		cy.get( '[data-post-type="post"]' ).within( () => {
			cy.get( '.beyondwords-setting__preselect--enabled' ).uncheck();
			cy.get( '.beyondwords-setting__preselect--options' ).should(
				'not.be.visible'
			);

			cy.get( '.beyondwords-setting__preselect--enabled' ).check();
			cy.get( '.beyondwords-setting__preselect--all' )
				.should( 'be.visible' )
				.and( 'be.checked' );
			cy.get( '.beyondwords-setting__preselect--taxonomies' ).should(
				'not.be.visible'
			);
		} );
	} );
} );
