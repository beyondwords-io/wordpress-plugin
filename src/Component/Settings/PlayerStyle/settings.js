/* global jQuery */

( function ( $ ) {
	'use strict';

	const $playerStyleFieldRow = $( '#beyondwords-plugin-settings' )
		.find( '.beyondwords-setting--player--player-style' )
		.closest( 'tr' );

	const $playerVersionField = $( '#beyondwords-plugin-settings' ).find(
		'.beyondwords-setting--player--player-version select'
	);

	const $playerUiField = $( '#beyondwords-plugin-settings' ).find(
		'.beyondwords-setting--player--player-ui select'
	);

	$playerVersionField.on( 'change', toggleFieldRow );

	$playerUiField.on( 'change', toggleFieldRow );

	// Toggle on page load
	toggleFieldRow();

	/**
	 * Only show this field row when "Player version" is "enabled" and
	 * "Player UI" is "Enabled".
	 */
	function toggleFieldRow() {
		const playerVersion = $playerVersionField.find( ':selected' ).val();
		const playerUi = $playerUiField.find( ':selected' ).val();

		if ( playerVersion === '1' && playerUi === 'enabled' ) {
			$playerStyleFieldRow.show();
		} else {
			$playerStyleFieldRow.hide();
		}
	}
} )( jQuery );
