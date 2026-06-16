/*
 * Preferences → "Preselect 'Generate audio'" settings field.
 *
 * Enables the hierarchical term checkboxes only while a post type's mode is
 * "Preselect only for specific terms", and dims them otherwise. Progressive
 * enhancement: the checkboxes are rendered enabled, so the field still works
 * without JS (the server only stores terms when 'terms' mode is submitted).
 *
 * Vanilla JS — no jQuery.
 */
( function () {
	'use strict';

	const TERMS_VALUE = 'terms';

	const syncFieldset = ( fieldset ) => {
		const terms = fieldset.querySelector(
			'.beyondwords-setting__preselect--terms'
		);
		if ( ! terms ) {
			return;
		}

		const selected = fieldset.querySelector(
			'input[type="radio"]:checked'
		);
		const isTerms = !! selected && selected.value === TERMS_VALUE;

		terms.classList.toggle( 'is-disabled', ! isTerms );
		terms
			.querySelectorAll( 'input[type="checkbox"]' )
			.forEach( ( checkbox ) => {
				checkbox.disabled = ! isTerms;
			} );
	};

	const init = () => {
		const fieldsets = document.querySelectorAll(
			'.beyondwords-setting__preselect--post-type'
		);

		fieldsets.forEach( ( fieldset ) => {
			fieldset
				.querySelectorAll( 'input[type="radio"]' )
				.forEach( ( radio ) =>
					radio.addEventListener( 'change', () =>
						syncFieldset( fieldset )
					)
				);

			syncFieldset( fieldset );
		} );
	};

	if ( document.readyState !== 'loading' ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}
} )();
