/* global jQuery */

( function ( $ ) {
	'use strict';

	$(
		'#beyondwords-plugin-settings .beyondwords-setting-categories-all input'
	).on( 'change', function () {
		if ( this.checked ) {
			$(
				'#beyondwords-plugin-settings .beyondwords-setting-categories-term'
			).hide();
			$(
				'#beyondwords-plugin-settings .beyondwords-setting-categories-term input'
			).prop( 'checked', false );
		} else {
			$(
				'#beyondwords-plugin-settings .beyondwords-setting-categories-term'
			).show();
		}
	} );
} )( jQuery );
