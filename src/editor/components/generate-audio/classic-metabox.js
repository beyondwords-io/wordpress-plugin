/* global beyondwordsPreselect */

/*
 * Classic-editor term-gated preselect.
 *
 * When the publisher has chosen to preselect "Generate audio" only for posts
 * with specific hierarchical taxonomy terms, keep the checkbox in step as the
 * editor ticks/unticks those terms. The server sets the correct initial state
 * on page load (via Preselect::should_preselect_for_post); this only handles
 * live changes — and stops once the user toggles Generate audio themselves, so
 * a deliberate choice is never clobbered (mirrors the block editor).
 *
 * Vanilla JS — no jQuery. Listens via event delegation so terms added through
 * the "+ Add New Category" UI are covered too.
 */
( function () {
	'use strict';

	const data =
		typeof beyondwordsPreselect !== 'undefined'
			? beyondwordsPreselect
			: null;

	if ( ! data || data.mode !== 'terms' || ! data.terms ) {
		return;
	}

	// The classic metabox input name for a taxonomy's term checkboxes.
	const inputNameFor = ( taxonomy ) =>
		taxonomy === 'category'
			? 'post_category[]'
			: `tax_input[${ taxonomy }][]`;

	const watched = Object.keys( data.terms ).map( ( taxonomy ) => ( {
		inputName: inputNameFor( taxonomy ),
		wanted: new Set( ( data.terms[ taxonomy ] || [] ).map( String ) ),
	} ) );

	// Once the editor toggles Generate audio by hand, stop auto-managing it.
	let userToggled = false;

	const generateAudioCheckbox = () =>
		document.getElementById( 'beyondwords_generate_audio' );

	// Does any currently-ticked term match the preselect rule (OR semantics)?
	const anyTermMatches = () =>
		watched.some( ( { inputName, wanted } ) => {
			const checked = document.querySelectorAll(
				`input[name="${ inputName }"]:checked`
			);
			return Array.prototype.some.call( checked, ( input ) =>
				wanted.has( String( input.value ) )
			);
		} );

	const sync = () => {
		if ( userToggled ) {
			return;
		}
		const checkbox = generateAudioCheckbox();
		if ( checkbox ) {
			checkbox.checked = anyTermMatches();
		}
	};

	const isWatchedInput = ( target ) =>
		!! target &&
		target.type === 'checkbox' &&
		watched.some( ( { inputName } ) => target.name === inputName );

	document.addEventListener( 'change', ( event ) => {
		const target = event.target;

		// A manual toggle of Generate audio freezes auto-management.
		if ( target && target.id === 'beyondwords_generate_audio' ) {
			userToggled = true;
			return;
		}

		if ( isWatchedInput( target ) ) {
			sync();
		}
	} );
} )();
