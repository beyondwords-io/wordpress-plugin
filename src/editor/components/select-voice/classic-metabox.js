/* global beyondwordsData */

/*
 * Classic-editor Voice + Model behaviour.
 *
 * The Language dropdown drives a voices fetch; the Voice dropdown lists
 * distinct voice names; the Model dropdown selects the variant (voice id) within
 * a name and is hidden for single-model voices. Mirrors the block editor's
 * voice-section.js + helpers.js.
 *
 * Vanilla JS — no jQuery dependency. The selects live in the post <form>, so
 * their values submit on save; no autosave/Heartbeat hook is needed.
 */
( function () {
	'use strict';

	const data =
		typeof beyondwordsData !== 'undefined' ? beyondwordsData : null;

	const ELEVENLABS = ( data && data.elevenLabs ) || 'ElevenLabs';
	const DEFAULT_MODEL_ID =
		( data && data.defaultModelId ) || 'eleven_multilingual_v2';
	const PROJECT_DEFAULT =
		( data && data.projectDefault ) || 'Project default';
	const MODEL_LABELS = ( data && data.voiceModelLabels ) || {};

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

	const option = ( value, text ) => {
		const el = document.createElement( 'option' );
		el.value = value;
		el.textContent = text;
		return el;
	};

	const toggleLoader = ( show ) => {
		const loader = document.querySelector(
			'.beyondwords-settings__loader'
		);
		if ( loader ) {
			loader.style.display = show ? '' : 'none';
		}
	};

	const selectVoice = {
		voices: [],

		// Monotonic id of the most recent getVoices() request. Out-of-order
		// responses compare against this and bail, so a slow earlier request
		// can never overwrite a faster later one ( "latest request wins" ).
		latestRequestId: 0,

		init() {
			if ( ! data ) {
				// eslint-disable-next-line no-console
				console.log( '🔊 Unable to retrive WP REST API settings' );
				return;
			}

			const language = document.getElementById(
				'beyondwords_language_code'
			);
			const voice = document.getElementById( 'beyondwords_voice' );

			if ( language ) {
				language.addEventListener( 'change', ( event ) => {
					this.getVoices( event.target.value );
				} );
			}

			if ( voice ) {
				voice.addEventListener( 'change', ( event ) => {
					this.setVoiceName( event.target.value );
				} );
			}
		},

		/**
		 * Get voices for a language, then rebuild the Voice + Model dropdowns.
		 *
		 * @param {string} languageCode The language code.
		 */
		getVoices( languageCode ) {
			// Claim the latest-request slot up front so that every call —
			// including the empty-languageCode early return below — invalidates
			// any still-in-flight request and stops its response clobbering the
			// dropdowns the user is now looking at.
			const requestId = ++this.latestRequestId;

			const voiceSelect = document.getElementById( 'beyondwords_voice' );

			if ( voiceSelect ) {
				voiceSelect.replaceChildren();
				voiceSelect.disabled = true;
				voiceSelect.style.display = 'none';
			}

			this.setModelOptions( [] );
			toggleLoader( true );

			if ( ! languageCode ) {
				toggleLoader( false );
				return;
			}

			const endpoint = `${ data.root }beyondwords/v1/languages/${ languageCode }/voices`;

			window
				.fetch( endpoint, {
					method: 'GET',
					headers: { 'X-WP-Nonce': data.nonce },
				} )
				.then( ( response ) => response.json() )
				.then( ( voices ) => {
					// A newer request superseded this one — discard the stale
					// response so the dropdowns keep the latest language's voices.
					if ( requestId !== this.latestRequestId ) {
						return;
					}
					this.voices = voices || [];
					this.renderVoiceNames();
				} )
				.catch( ( error ) => {
					// Don't let a superseded request's failure wipe the voices
					// a later request already rendered.
					if ( requestId !== this.latestRequestId ) {
						return;
					}
					// eslint-disable-next-line no-console
					console.log( '🔊 Unable to load voices', error );
					this.voices = [];
					if ( voiceSelect ) {
						voiceSelect.replaceChildren();
						voiceSelect.disabled = true;
					}
				} )
				.finally( () => {
					// Only the latest request owns the loader; a superseded
					// request must not hide it while the newer one is pending.
					if ( requestId !== this.latestRequestId ) {
						return;
					}
					toggleLoader( false );
				} );
		},

		/**
		 * Rebuild the Voice (name) dropdown from the current voices list, reset
		 * to Project default, and clear the Model dropdown.
		 */
		renderVoiceNames() {
			const voiceSelect = document.getElementById( 'beyondwords_voice' );

			if ( ! voiceSelect ) {
				return;
			}

			const names = [];
			this.voices.forEach( ( voice ) => {
				if ( voice.name && ! names.includes( voice.name ) ) {
					names.push( voice.name );
				}
			} );

			voiceSelect.replaceChildren(
				option( '', PROJECT_DEFAULT ),
				...names.map( ( name ) => option( name, name ) )
			);
			voiceSelect.disabled = false;
			voiceSelect.style.display = '';

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
			this.setModelOptions( voiceModelVariants( first, this.voices ) );
		},

		/**
		 * Replace the Model dropdown options and toggle its visibility.
		 *
		 * @param {Array<Object>} variants The voice's model variants.
		 */
		setModelOptions( variants ) {
			const modelSelect = document.getElementById(
				'beyondwords_voice_id'
			);
			const wrapper = document.getElementById(
				'beyondwords-metabox-select-voice--model'
			);

			const list = variants || [];

			if ( modelSelect ) {
				modelSelect.replaceChildren(
					...list.map( ( variant ) =>
						option( variant.id, modelLabel( variant.model_id ) )
					)
				);
			}

			if ( wrapper ) {
				wrapper.style.display = list.length > 1 ? '' : 'none';
			}
		},
	};

	if ( document.readyState !== 'loading' ) {
		selectVoice.init();
	} else {
		document.addEventListener( 'DOMContentLoaded', () =>
			selectVoice.init()
		);
	}
} )();
