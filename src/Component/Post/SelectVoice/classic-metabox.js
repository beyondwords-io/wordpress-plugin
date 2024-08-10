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
				console.log( '🔊 Unable to retrive WP REST API settings' );
				return;
			}

			this.setupClickEvents();
			this.setupAutosaveVariables();
		},

		/**
		 * Setup click events.
		 *
		 * @since 4.0.0
		 */
		setupClickEvents() {
			$( document ).on(
				'change',
				'select#beyondwords_language_id',
				function () {
					selectVoice.getVoices( this.value );
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
				const languageId = $( '#beyondwords_language_id' ).find( ':selected' ).val();
				const voiceId    = $( '#beyondwords_voice_id' ).find( ':selected' ).val();

				if ( languageId ) {
					settings.data +=
						'&' +
						$.param( {
							beyondwords_language_id: languageId,
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
		 * @since 4.0.0
		 */
		getVoices( languageId ) {
			const $voicesSelect = $( '#beyondwords_voice_id' );

			languageId = parseInt(languageId);

			if ( ! languageId ) {
				$voicesSelect.empty().attr( 'disabled', true );
				return;
			}

			const endpoint = `${beyondwordsData.root}beyondwords/v1/languages/${languageId}/voices`;

			jQuery.ajax( {
				url: endpoint,
				method: 'GET',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', beyondwordsData.nonce );
				}
			} ).done( function( voices ) {
				$voicesSelect
					.empty()
					.append( '<option value=""></option>' )
					.append( voices.map( ( voice ) => {
						return $( '<option></option>' ).val( voice.id ).text( voice.name );
					} ) )
					.attr( 'disabled', false );
			} ).fail(function ( xhr ) {
				console.log( '🔊 Unable to load voices', xhr );
				$voicesSelect.empty().attr( 'disabled', true )
			} );
		},
	};

	$( document ).ready( function () {
		selectVoice.init();
	} );
} )( jQuery );
