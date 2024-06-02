/* global jQuery, TomSelect */
'use strict';

( function ( $ ) {
	$( document ).ready( function () {
		if ($('#beyondwords_project_language').length) {
			const select = new TomSelect( '#beyondwords_project_language', {
				maxOptions: null,
				sortField: {
					field: "text",
					direction: "asc"
				}
			});

			select.on('change', function(value){
				console.log('value', value);
				// @todo add error notice here
				$('select[name="beyondwords_project_title_voice"]').attr('value', '').attr('disabled', 'disabled');
				$('select[name="beyondwords_project_body_voice"]').attr('value', '').attr('disabled', 'disabled');
			});
		}
	} );
} )( jQuery );

