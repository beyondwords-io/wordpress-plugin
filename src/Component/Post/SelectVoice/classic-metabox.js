/* global jQuery, beyondwordsData */

( function ( $ ) {
	'use strict';

	const selectVoice = {
		/**
		 * Init.
		 *
		 * @since 4.0.0
		 */
		init() {
			if ( ! beyondwordsData ) {
				// eslint-disable-next-line no-console
				console.log( '🔊 Unable to retrive WP REST API settings' );
				return;
			}

			this.setupClickEvents();
			this.setupAutosaveVariables();
		},

		/**
		 * Setup click events.
		 *
		 * @since 5.4.0
		 */
		setupClickEvents() {
			$( document ).on(
				'change',
				'select#beyondwords_language_code',
				function () {
					const defaultVoiceId = $( this )
						.find( ':selected' )
						.attr( 'data-default-voice-id' );

					selectVoice.getVoices( this.value, `${ defaultVoiceId }` );
				}
			);
		},

		/**
		 * Add our checkbox value to the autosave POST vars (if it's checked).
		 *
		 * @since 4.0.0
		 */
		setupAutosaveVariables() {
			$( document ).ajaxSend( function ( event, request, settings ) {
				const languageCode = $( '#beyondwords_language_code' )
					.find( ':selected' )
					.val();
				const voiceId = $( '#beyondwords_voice_id' )
					.find( ':selected' )
					.val();

				if ( languageCode ) {
					settings.data +=
						'&' +
						$.param( {
							beyondwords_language_code: languageCode,
						} );
				}

				if ( voiceId ) {
					settings.data +=
						'&' +
						$.param( {
							beyondwords_voice_id: voiceId,
						} );
				}
			} );
		},

		/**
		 * Get voices for a language.
		 *
		 * @since 5.4.0
		 *
		 * @param {string} languageCode
		 * @param {string} defaultVoiceId
		 */
		getVoices( languageCode, defaultVoiceId ) {
			const $voicesSelect = $( '#beyondwords_voice_id' );

			$voicesSelect.empty().attr( 'disabled', true ).hide();
			$( '.beyondwords-settings__loader' ).show();

			if ( ! languageCode ) {
				return;
			}

			const { root, nonce } = beyondwordsData;

			// eslint-disable-next-line max-len
			const endpoint = `${ root }beyondwords/v1/languages/${ languageCode }/voices`;

			jQuery
				.ajax( {
					url: endpoint,
					method: 'GET',
					beforeSend( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', nonce );
					},
				} )
				.done( function ( voices ) {
					$voicesSelect
						.empty()
						.show()
						.append(
							voices.map( ( voice ) => {
								return $( '<option></option>' )
									.val( voice.id )
									.text( voice.name )
									.attr(
										'selected',
										defaultVoiceId === `${ voice.id }`
									);
							} )
						)
						.attr( 'disabled', false );
				} )
				.fail( function ( xhr ) {
					// eslint-disable-next-line no-console
					console.log( '🔊 Unable to load voices', xhr );
					$voicesSelect.empty().attr( 'disabled', true );
				} )
				.always( function () {
					$( '.beyondwords-settings__loader' ).hide();
				} );
		},
	};

	$( document ).ready( function () {
		selectVoice.init();
	} );
} )( jQuery );
