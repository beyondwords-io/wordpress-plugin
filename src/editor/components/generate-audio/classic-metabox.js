/* global beyondwordsPreselect */

// Keeps the Generate audio caption in step with the checkbox and, in term-gated
// preselect mode, auto-syncs the checkbox from watched terms (mirrors block editor).
( function () {
	'use strict';

	const generateAudioCheckbox = () =>
		document.getElementById( 'beyondwords_generate_audio' );

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

	const data =
		typeof beyondwordsPreselect !== 'undefined'
			? beyondwordsPreselect
			: null;

	const termGating = !! ( data && data.mode === 'terms' && data.terms );

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

	// Align the caption with the server-rendered checkbox state up front.
	syncCaption();

	// Delegated so terms added via the "+ Add New Category" UI are covered too.
	document.addEventListener( 'change', ( event ) => {
		const target = event.target;

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
