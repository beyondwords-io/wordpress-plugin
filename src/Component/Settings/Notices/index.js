/* global beyondwordsData, jQuery */

( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		$( '#beyondwords_notice_review' ).on(
			'click',
			'.notice-dismiss',
			function ( event ) {
				event.preventDefault();

				// eslint-disable-next-line max-len
				const endpoint = `${ beyondwordsData.root }beyondwords/v1/settings/notices/review/dismiss`;

				$.ajax( {
					url: endpoint,
					method: 'POST',
					// eslint-disable-next-line object-shorthand
					beforeSend: function ( xhr ) {
						xhr.setRequestHeader(
							'X-WP-Nonce',
							beyondwordsData.nonce
						);
					},
					// eslint-disable-next-line object-shorthand
					success: function ( response ) {
						if ( response.success ) {
							$( '#beyondwords_notice_review' ).hide();
						} else {
							// eslint-disable-next-line no-console
							console.error(
								'🔊 REST API Error dismissing notice',
								response
							);
						}
					},
					// eslint-disable-next-line object-shorthand
					error: function ( error ) {
						// eslint-disable-next-line no-console
						console.error( '🔊 Error dismissing notice', error );
					},
				} );
			}
		);
	} );
} )( jQuery );
