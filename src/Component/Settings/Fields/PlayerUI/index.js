/* global jQuery */

( function ( $ ) {
	'use strict';

	const $playerUiField = $( '#beyondwords-plugin-settings' ).find(
		'.beyondwords-setting__player--player-ui select'
	);

	const $playerSettingsFields = $( '#beyondwords-plugin-settings' )
		.find( '.beyondwords-settings__player-field-toggle' );

	$playerUiField.on( 'change', toggleFieldRow );

	// Toggle on page load
	toggleFieldRow();

	/**
	 * Only show this field row when "Player UI" is "Enabled".
	 */
	function toggleFieldRow() {
		const playerUi = $playerUiField.find( ':selected' ).val();

		$playerSettingsFields.each(function( index ) {
			if ( playerUi === 'enabled' ) {
				jQuery(this).show();
			} else {
				jQuery(this).hide();
			}
		} );
	}
} )( jQuery );
