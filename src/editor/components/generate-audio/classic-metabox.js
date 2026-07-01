/* global beyondwordsPreselect */

/*
 * Classic-editor Generate audio behaviours.
 *
 * 1. State-reflecting caption — keep the "Generation enabled/disabled" label in
 *    step with the checkbox as it is ticked/unticked (mirrors the block editor
 *    toggle). The server renders the correct initial caption on page load.
 *
 * 2. Term-gated preselect (only when configured) — when the publisher has chosen
 *    to preselect "Generate audio" only for posts with specific hierarchical
 *    taxonomy terms, keep the checkbox in step as the editor ticks/unticks those
 *    terms. The server sets the correct initial state on page load (via
 *    Preselect::should_preselect_for_post); this only handles live changes — and
 *    stops once the user toggles the checkbox themselves, so a deliberate choice
 *    is never clobbered (mirrors the block editor).
 *
 * Vanilla JS — no jQuery. Listens via event delegation so terms added through
 * the "+ Add New Category" UI are covered too.
 */
( function () {
	'use strict';

	const generateAudioCheckbox = () =>
		document.getElementById( 'beyondwords_generate_audio' );

	/* ---- 1. State-reflecting caption ---- */

	const captionEl = () =>
		document.getElementById( 'beyondwords-generate-audio-label' );

	const syncCaption = () => {
		const caption = captionEl();
		const checkbox = generateAudioCheckbox();
		if ( ! caption || ! checkbox ) {
			return;
		}
		const label = checkbox.checked
			? caption.getAttribute( 'data-label-enabled' )
			: caption.getAttribute( 'data-label-disabled' );
		if ( label !== null ) {
			caption.textContent = label;
		}
	};

	/* ---- 2. Term-gated preselect (optional) ---- */

	const data =
		typeof beyondwordsPreselect !== 'undefined'
			? beyondwordsPreselect
			: null;

	const termGating = !! ( data && data.mode === 'terms' && data.terms );

	// The classic metabox input name for a taxonomy's term checkboxes.
	const inputNameFor = ( taxonomy ) =>
		taxonomy === 'category'
			? 'post_category[]'
			: `tax_input[${ taxonomy }][]`;

	const watched = termGating
		? Object.keys( data.terms ).map( ( taxonomy ) => ( {
				inputName: inputNameFor( taxonomy ),
				wanted: new Set(
					( data.terms[ taxonomy ] || [] ).map( String )
				),
		  } ) )
		: [];

	// Once the editor toggles Generate audio by hand, stop auto-managing it.
	let userToggled = false;

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

	const syncFromTerms = () => {
		if ( userToggled ) {
			return;
		}
		const checkbox = generateAudioCheckbox();
		if ( checkbox ) {
			checkbox.checked = anyTermMatches();
			syncCaption();
		}
	};

	const isWatchedInput = ( target ) =>
		!! target &&
		target.type === 'checkbox' &&
		watched.some( ( { inputName } ) => target.name === inputName );

	/* ---- Wire up ---- */

	// Align the caption with the server-rendered checkbox state up front.
	syncCaption();

	document.addEventListener( 'change', ( event ) => {
		const target = event.target;

		// A manual toggle of Generate audio updates the caption and (when
		// term-gating) freezes auto-management.
		if ( target && target.id === 'beyondwords_generate_audio' ) {
			userToggled = true;
			syncCaption();
			return;
		}

		if ( termGating && isWatchedInput( target ) ) {
			syncFromTerms();
		}
	} );
} )();
