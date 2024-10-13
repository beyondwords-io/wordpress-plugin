/* global jQuery */

( function ( $ ) {
	'use strict';

	const $fields = $(
		'#beyondwords-plugin-settings .beyondwords-setting__preselect--post-type'
	);

	$fields.each( function () {
		if ( $( this ).find( '> label > input' ).is( ':checked' ) ) {
			$( this )
				.find( '.beyondwords-setting__preselect--taxonomy' )
				.hide();
		}
	} );

	$fields.on( 'change', '> label > input', function () {
		const $postType = $( this ).closest(
			'.beyondwords-setting__preselect--post-type'
		);
		if ( this.checked ) {
			$postType
				.find( '.beyondwords-setting__preselect--taxonomy' )
				.hide();
			$postType
				.find( '.beyondwords-setting__preselect--term input' )
				.prop( 'checked', false );
		} else {
			$postType
				.find( '.beyondwords-setting__preselect--taxonomy' )
				.show();
		}
	} );
} )( jQuery );
