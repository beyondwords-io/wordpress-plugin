/* global jQuery */

( function ( $ ) {
	'use strict';

	const $playerUiFieldRow = $( '#beyondwords-plugin-settings' )
		.find( '.beyondwords-setting--player--player-ui' )
		.closest( 'tr' );

	const $playerVersionField = $( '#beyondwords-plugin-settings' ).find(
		'.beyondwords-setting--player--player-version select'
	);

	$playerVersionField.on( 'change', function () {
		togglePlayerUIRow( this.value );
	} );

	// Toggle on page load
	togglePlayerUIRow( $playerVersionField.find( ':selected' ).val() );

	/**
	 * The Player UI settings field is only applicable to the "Latest" player,
	 * so hide it if the "Legacy" player is being used.
	 *
	 * @param {string} playerVersion "1" (Latest) or "0" (Legacy)
	 */
	function togglePlayerUIRow( playerVersion ) {
		if ( playerVersion === '0' ) {
			$playerUiFieldRow.hide();
		} else {
			$playerUiFieldRow.show();
		}
	}
} )( jQuery );
