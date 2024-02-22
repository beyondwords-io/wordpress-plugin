/* global jQuery, ClipboardJS */

jQuery( document ).ready( function ( $ ) {
	const clipboard = new ClipboardJS( '#beyondwords__inspect--copy' );

	clipboard.on( 'success', function () {
		$( '#beyondwords__inspect--copy-confirm' ).show();
	} );

	$( 'body' ).on( 'click', '#beyondwords__inspect--remove', function () {
		/* eslint-disable-next-line no-alert */
		const confirm = window.confirm(
			wp.i18n.__(
				'Remove all BeyondWords data when the post is saved?',
				'speechkit'
			)
		);

		if ( confirm ) {
			$( '#beyondwords_delete_content' ).removeAttr( 'disabled' );
			$( '[data-beyondwords-metavalue]' ).val( '' );
		}
	} );
} );
