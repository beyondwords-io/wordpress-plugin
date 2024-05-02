/* global jQuery */

( function ( $ ) {
	'use strict';

	// Sadly not got this working yet...
	// $('#beyondwords-plugin-settings').tabs();

	// ...so for now we use a janky manual approach, just for review...

	var currentTab = $('.nav-tab.nav-tab-active').data('tab');

    $('section').hide();
    $('section.' + currentTab).show();

	$(document).on( 'click', 'a.nav-tab', function() {
		$('section').hide();
		$('section').eq($(this).index()).show();
		$('a.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');

		const url = new URL(window.location.href);
		url.searchParams.set('tab', $(this).data('tab'));
		window.history.pushState(null, '', url.toString());

		return false;
	});

} )( jQuery );
