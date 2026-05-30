/* global jQuery, beyondwordsData */

/*
 * Classic-editor Voice + Model behaviour.
 *
 * The Language dropdown drives an AJAX voices fetch; the Voice dropdown lists
 * distinct voice names; the Model dropdown selects the variant (voice id) within
 * a name and is hidden for single-model voices. Mirrors the block editor's
 * voice-section.js + helpers.js.
 */
( function ( $ ) {
	'use strict';

	const ELEVENLABS =
		( beyondwordsData && beyondwordsData.elevenLabs ) || 'ElevenLabs';
	const DEFAULT_MODEL_ID =
		( beyondwordsData && beyondwordsData.defaultModelId ) ||
		'eleven_multilingual_v2';
	const PROJECT_DEFAULT =
		( beyondwordsData && beyondwordsData.projectDefault ) ||
		'Project default';
	const MODEL_LABELS =
		( beyondwordsData && beyondwordsData.voiceModelLabels ) || {};

	/**
	 * Human label for a model_id slug.
	 *
	 * @param {string} modelId The model_id slug.
	 *
	 * @return {string} A display label.
	 */
	const modelLabel = ( modelId ) => {
		if ( MODEL_LABELS[ modelId ] ) {
			return MODEL_LABELS[ modelId ];
		}
		return String( modelId )
			.replace( /^eleven_/, '' )
			.replace( /_/g, ' ' )
			.replace( /\b\w/g, ( c ) => c.toUpperCase() );
	};

	/**
	 * The model variants for a voice, the default model first.
	 *
	 * @param {Object}        voice  The selected voice record.
	 * @param {Array<Object>} voices All voices for the current language.
	 *
	 * @return {Array<Object>} The voice's model variants.
	 */
	const voiceModelVariants = ( voice, voices ) => {
		if (
			! voice ||
			voice.service !== ELEVENLABS ||
			typeof voice.model_id !== 'string'
		) {
			return voice ? [ voice ] : [];
		}

		return ( voices || [] )
			.filter(
				( candidate ) =>
					candidate.name === voice.name &&
					candidate.service === ELEVENLABS &&
					typeof candidate.model_id === 'string'
			)
			.sort( ( a, b ) => {
				if ( a.model_id === DEFAULT_MODEL_ID ) {
					return -1;
				}
				if ( b.model_id === DEFAULT_MODEL_ID ) {
					return 1;
				}
				return 0;
			} );
	};

	const selectVoice = {
		voices: [],

		init() {
			if ( ! beyondwordsData ) {
				// eslint-disable-next-line no-console
				console.log( '🔊 Unable to retrive WP REST API settings' );
				return;
			}

			this.setupClickEvents();
			this.setupAutosaveVariables();
		},

		setupClickEvents() {
			$( document ).on(
				'change',
				'select#beyondwords_language_code',
				function () {
					selectVoice.getVoices( this.value );
				}
			);

			$( document ).on(
				'change',
				'select#beyondwords_voice',
				function () {
					selectVoice.setVoiceName( this.value );
				}
			);
		},

		/**
		 * Add our select values to the autosave POST vars.
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
		 * Get voices for a language, then rebuild the Voice + Model dropdowns.
		 *
		 * @param {string} languageCode The language code.
		 */
		getVoices( languageCode ) {
			const $voicesSelect = $( '#beyondwords_voice' );

			$voicesSelect.empty().attr( 'disabled', true ).hide();
			this.setModelOptions( [] );
			$( '.beyondwords-settings__loader' ).show();

			if ( ! languageCode ) {
				$( '.beyondwords-settings__loader' ).hide();
				return;
			}

			const { root, nonce } = beyondwordsData;
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
					selectVoice.voices = voices || [];
					selectVoice.renderVoiceNames();
				} )
				.fail( function ( xhr ) {
					// eslint-disable-next-line no-console
					console.log( '🔊 Unable to load voices', xhr );
					selectVoice.voices = [];
					$voicesSelect.empty().attr( 'disabled', true );
				} )
				.always( function () {
					$( '.beyondwords-settings__loader' ).hide();
				} );
		},

		/**
		 * Rebuild the Voice (name) dropdown from the current voices list, reset
		 * to Project default, and clear the Model dropdown.
		 */
		renderVoiceNames() {
			const $voicesSelect = $( '#beyondwords_voice' );
			const names = [];

			this.voices.forEach( ( voice ) => {
				if ( voice.name && ! names.includes( voice.name ) ) {
					names.push( voice.name );
				}
			} );

			$voicesSelect
				.empty()
				.show()
				.append(
					$( '<option></option>' ).val( '' ).text( PROJECT_DEFAULT )
				)
				.append(
					names.map( ( name ) =>
						$( '<option></option>' ).val( name ).text( name )
					)
				)
				.attr( 'disabled', false );

			this.setModelOptions( [] );
		},

		/**
		 * Pick a voice name → select that name's default model variant.
		 *
		 * @param {string} name The selected voice name.
		 */
		setVoiceName( name ) {
			if ( ! name ) {
				this.setModelOptions( [] );
				return;
			}

			const first = this.voices.find( ( voice ) => voice.name === name );
			const variants = voiceModelVariants( first, this.voices );

			this.setModelOptions( variants );
		},

		/**
		 * Replace the Model dropdown options and toggle its visibility.
		 *
		 * @param {Array<Object>} variants The voice's model variants.
		 */
		setModelOptions( variants ) {
			const $modelSelect = $( '#beyondwords_voice_id' );
			const $wrapper = $( '#beyondwords-metabox-select-voice--model' );

			$modelSelect
				.empty()
				.append(
					( variants || [] ).map( ( variant ) =>
						$( '<option></option>' )
							.val( variant.id )
							.text( modelLabel( variant.model_id ) )
					)
				);

			$wrapper.toggle( ( variants || [] ).length > 1 );
		},
	};

	$( document ).ready( function () {
		selectVoice.init();
	} );
} )( jQuery );
