/* global jQuery */

( function ( $ ) {
	'use strict';

	const $playerStyleFieldRow = $( '#beyondwords-plugin-settings' )
		.find( '.beyondwords-setting--player--player-style' )
		.closest( 'tr' );

	const $playerUiField = $( '#beyondwords-plugin-settings' ).find(
		'.beyondwords-setting--player--player-ui select'
	);

	$playerUiField.on( 'change', toggleFieldRow );

	// Toggle on page load
	toggleFieldRow();

	/**
	 * Only show this field row when "Player UI" is "Enabled".
	 */
	function toggleFieldRow() {
		const playerUi = $playerUiField.find( ':selected' ).val();

		if ( playerUi === 'enabled' ) {
			$playerStyleFieldRow.show();
		} else {
			$playerStyleFieldRow.hide();
		}
	}
} )( jQuery );
