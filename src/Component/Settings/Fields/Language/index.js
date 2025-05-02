/* global jQuery, TomSelect */
'use strict';
( function ( $ ) {
	$( document ).ready( function () {
		const originalLanguageCode = $('#beyondwords_project_language_code').value;

		if ($('#beyondwords_project_language_code').length) {
			const select = new TomSelect( '#beyondwords_project_language_code', {
				maxOptions: null,
				sortField: {
					field: "text",
					direction: "asc"
				}
			});

			select.on('change', async function(languageCode){
				const $voicesSelects     = $( '.beyondwords_project_voice' );
				const $titleVoicesSelect = $( '#beyondwords_project_title_voice_id' );
				const $bodyVoicesSelect  = $( '#beyondwords_project_body_voice_id' );
				const endpoint           = `${beyondwordsData.root}beyondwords/v1/languages/${languageCode}/voices`;

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
								return $( '<option></option>' )
									.val( voice.id )
									.text( voice.name );
							} ) )
							.attr( 'disabled', false );
					})

					const defaultVoices = $(`#beyondwords_project_language_code option[value="${languageCode}"]`).data( 'voices' )

					if (defaultVoices) {
						if (defaultVoices.title && defaultVoices.title.id) {
							$($titleVoicesSelect).find(`option[value="${defaultVoices.title.id}"]`).prop('selected', true);
						}
						if (defaultVoices.body && defaultVoices.body.id) {
							$($bodyVoicesSelect).find(`option[value="${defaultVoices.body.id}"]`).prop('selected', true);
						}
						if (defaultVoices.title && defaultVoices.title.speaking_rate) {
							$('#beyondwords_project_title_voice_speaking_rate').val(defaultVoices.title.speaking_rate);
						}
						if (defaultVoices.body && defaultVoices.body.speaking_rate) {
							$('#beyondwords_project_body_voice_speaking_rate').val(defaultVoices.body.speaking_rate);
						}
					}
				} ).fail(function ( xhr ) {
					console.log( '🔊 Unable to load voices', xhr );
					$('#beyondwords_project_language_code').setValue(originalLanguageCode);
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
