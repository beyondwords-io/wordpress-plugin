/* global jQuery */

( function ( $ ) {
	'use strict';

	$(document).ready(function() {
		$( "#tabs" ).tabs({
			activate: function(event, ui) {
				$('#tabs li a').removeClass('nav-tab-active');
				$('#tabs li.ui-state-active a').addClass('nav-tab-active');
				window.history.pushState(
					null,
					'',
					'#' + ui.newPanel.attr( 'id' )
				);
				return false;
			},
			classes: {
				"ui-tabs": "nav-tab-active",
				"ui-tabs-nav": "",
				"ui-tabs-tab": "nav-tab-active",
				"ui-tabs-panel": ""
			}
		});
	});

} )( jQuery );
