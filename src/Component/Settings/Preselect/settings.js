/* global jQuery */

( function ( $ ) {
	'use strict';

	const $fields = $(
		'#beyondwords-plugin-settings .beyondwords-setting--preselect--post-type'
	);

	$fields.each( function () {
		if ( $( this ).find( '> label > input' ).is( ':checked' ) ) {
			$( this )
				.find( '.beyondwords-setting--preselect--taxonomy' )
				.hide();
		}
	} );

	$fields.on( 'change', '> label > input', function () {
		const $postType = $( this ).closest(
			'.beyondwords-setting--preselect--post-type'
		);
		if ( this.checked ) {
			$postType
				.find( '.beyondwords-setting--preselect--taxonomy' )
				.hide();
			$postType
				.find( '.beyondwords-setting--preselect--term input' )
				.prop( 'checked', false );
		} else {
			$postType
				.find( '.beyondwords-setting--preselect--taxonomy' )
				.show();
		}
	} );
} )( jQuery );
