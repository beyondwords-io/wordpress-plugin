/* global ClipboardJS */

/*
 * Classic-editor Inspect panel: copy-to-clipboard confirmation + "Remove all
 * BeyondWords data" toggle. Vanilla JS — no jQuery dependency. ClipboardJS is a
 * standalone library (not jQuery).
 */
( function () {
	'use strict';

	function init() {
		const copyButton = document.getElementById(
			'beyondwords__inspect--copy'
		);

		if ( copyButton && typeof ClipboardJS !== 'undefined' ) {
			const clipboard = new ClipboardJS( '#beyondwords__inspect--copy' );

			clipboard.on( 'success', function () {
				const confirm = document.getElementById(
					'beyondwords__inspect--copy-confirm'
				);
				if ( confirm ) {
					confirm.style.display = '';
				}
			} );
		}

		document.addEventListener( 'click', function ( event ) {
			if ( ! event.target.closest( '#beyondwords__inspect--remove' ) ) {
				return;
			}

			/* eslint-disable-next-line no-alert */
			const confirmed = window.confirm(
				wp.i18n.__(
					'Remove all BeyondWords data when the post is saved?',
					'speechkit'
				)
			);

			if ( ! confirmed ) {
				return;
			}

			const deleteInput = document.getElementById(
				'beyondwords_delete_content'
			);
			if ( deleteInput ) {
				deleteInput.removeAttribute( 'disabled' );
			}

			document
				.querySelectorAll( '[data-beyondwords-metavalue]' )
				.forEach( ( el ) => {
					el.value = '';
				} );
		} );
	}

	if ( document.readyState !== 'loading' ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}
} )();
