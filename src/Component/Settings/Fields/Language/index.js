/* global jQuery, TomSelect */
'use strict';

( function ( $ ) {
	$( document ).ready( function () {
		const originalLanguageCode = $('#beyondwords_project_language').value;

		if ($('#beyondwords_project_language').length) {
			const select = new TomSelect( '#beyondwords_project_language', {
				maxOptions: null,
				sortField: {
					field: "text",
					direction: "asc"
				}
			});

			select.on('change', async function(languageCode){
				const $voicesSelects = $( '.beyondwords_project_voice' );
				const endpoint       = `${beyondwordsData.root}beyondwords/v1/languages/${languageCode}/voices`;

				$('.beyondwords-settings__loader-default-language').show();
				$('select.beyondwords_project_voice').hide();
				$('select.beyondwords_project_voice').attr('value', '').attr('disabled', 'disabled');
				$('.beyondwords-setting__title-voice .beyondwords-settings__loader').show();
				$('.beyondwords-setting__body-voice .beyondwords-settings__loader').show();
				$('.beyondwords_speaking_rate').attr('disabled', 'disabled');

				jQuery.ajax( {
					url: endpoint,
					method: 'GET',
					beforeSend: function ( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', beyondwordsData.nonce );
					}
				} ).done( function( voices ) {
					$voicesSelects.each(function( index ) {
						$(this)
							.empty()
							.append( voices.map( ( voice ) => {
								return $( '<option></option>' ).val( voice.id ).text( voice.name );
							} ) )
							.attr( 'disabled', false );
					})
				} ).fail(function ( xhr ) {
					console.log( '🔊 Unable to load voices', xhr );
					$('#beyondwords_project_language').setValue(originalLanguageCode);
				} ).always(function () {
					$('.beyondwords-setting__title-voice .beyondwords-settings__loader').hide();
					$('.beyondwords-setting__body-voice .beyondwords-settings__loader').hide();
					$('select.beyondwords_project_voice').show();
					$('select.beyondwords_project_voice').attr('value', '').attr('disabled', false);
					$('.beyondwords_speaking_rate').attr('disabled', false);
				});
			});
		}
	} );
} )( jQuery );

