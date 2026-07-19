/*
 * Preferences → "Preselect 'Generate audio'" field: progressive disclosure.
 *
 * Checkboxes render server-side with their correct initial visibility, so this
 * is enhancement only; hiding the term trees never clears their selections.
 */
( function () {
	'use strict';

	const sync = ( fieldset ) => {
		const enabled = fieldset.querySelector(
			'.beyondwords-setting__preselect--enabled'
		);
		const options = fieldset.querySelector(
			'.beyondwords-setting__preselect--options'
		);

		if ( ! enabled || ! options ) {
			return;
		}

		const all = options.querySelector(
			'.beyondwords-setting__preselect--all'
		);
		const taxonomies = options.querySelector(
			'.beyondwords-setting__preselect--taxonomies'
		);

		options.style.display = enabled.checked ? '' : 'none';

		if ( taxonomies && all ) {
			taxonomies.style.display =
				enabled.checked && ! all.checked ? '' : 'none';
		}
	};

	const init = () => {
		const fieldsets = document.querySelectorAll(
			'.beyondwords-setting__preselect--post-type'
		);

		fieldsets.forEach( ( fieldset ) => {
			const enabled = fieldset.querySelector(
				'.beyondwords-setting__preselect--enabled'
			);
			const all = fieldset.querySelector(
				'.beyondwords-setting__preselect--all'
			);

			if ( enabled ) {
				enabled.addEventListener( 'change', () => {
					// Enabling defaults to "All" (preselect the whole type).
					if ( enabled.checked && all ) {
						all.checked = true;
					}
					sync( fieldset );
				} );
			}

			if ( all ) {
				all.addEventListener( 'change', () => sync( fieldset ) );
			}

			sync( fieldset );
		} );
	};

	if ( document.readyState !== 'loading' ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}
} )();
