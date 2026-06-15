/* global beyondwordsData */

/*
 * Classic-editor Customize + Voice + Model behaviour.
 *
 * "Customize" is opt-in. While off, the post uses the project default language
 * and voice: the fields stay hidden and the selects submit empty so save()
 * removes the meta. While on, the Language dropdown drives a voices fetch and
 * seeds the language's default voice (we never send the language itself — the
 * voice carries it); the Voice dropdown lists distinct voice names; the Model
 * dropdown selects the variant (voice id) within a name and is hidden for
 * single-model voices. Mirrors the block editor's voice-section.js + helpers.js.
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
	const SELECT_VOICE = ( data && data.selectVoice ) || 'Select a voice';
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
		voicesReq: 0,

		init() {
			if ( ! data ) {
				// eslint-disable-next-line no-console
				console.log( '🔊 Unable to retrive WP REST API settings' );
				return;
			}

			const customize = document.getElementById(
				'beyondwords_customize'
			);
			const language = document.getElementById(
				'beyondwords_language_code'
			);
			const voice = document.getElementById( 'beyondwords_voice' );

			if ( customize ) {
				customize.addEventListener( 'change', ( event ) => {
					this.toggleCustomize( event.target.checked );
				} );
			}

			if ( language ) {
				language.addEventListener( 'change', ( event ) => {
					const select = event.target;
					const selected = select.options[ select.selectedIndex ];
					const defaultVoiceId = selected
						? selected.getAttribute( 'data-default-voice-id' )
						: '';
					this.getVoices( select.value, defaultVoiceId );
				} );
			}

			if ( voice ) {
				voice.addEventListener( 'change', ( event ) => {
					this.setVoiceName( event.target.value );
				} );
			}
		},

		/**
		 * Show/hide the language + voice fields. Turning Customize off reverts the
		 * post to the project defaults by clearing the selects, so they submit
		 * empty and save() removes the meta.
		 *
		 * @param {boolean} on Whether Customize is enabled.
		 */
		toggleCustomize( on ) {
			const fields = document.getElementById(
				'beyondwords-metabox-select-voice--fields'
			);
			if ( fields ) {
				fields.style.display = on ? '' : 'none';
			}

			if ( on ) {
				this.applyProjectDefaultLanguage();
				return;
			}

			const language = document.getElementById(
				'beyondwords_language_code'
			);
			if ( language ) {
				language.value = '';
			}

			const voiceSelect = document.getElementById( 'beyondwords_voice' );
			if ( voiceSelect ) {
				voiceSelect.replaceChildren();
				voiceSelect.disabled = true;
				voiceSelect.style.display = 'none';
			}

			this.voices = [];
			this.setModelOptions( [] );
		},

		/**
		 * On Customize-on, fetch the project's default language and pre-select it
		 * — only the language; the user picks the Voice (so it stays "Select a
		 * voice"). A spinner shows while the language and its voices resolve. On
		 * failure, or when the post already has a language, fall back to the
		 * manual "pick a language" flow.
		 */
		applyProjectDefaultLanguage() {
			const language = document.getElementById(
				'beyondwords_language_code'
			);

			// Only pre-fill a fresh post; never override an existing choice.
			if ( ! language || language.value || ! data.projectId ) {
				return;
			}

			toggleLoader( true );

			const endpoint = `${ data.root }beyondwords/v1/projects/${ data.projectId }`;

			window
				.fetch( endpoint, {
					method: 'GET',
					headers: { 'X-WP-Nonce': data.nonce },
				} )
				.then( ( response ) => response.json() )
				.then( ( project ) => {
					// Bail if Customize was switched off, or a language was
					// chosen, while the request was in flight — otherwise we'd
					// re-show the fields and persist a language on a post the
					// user left un-customised.
					const customize = document.getElementById(
						'beyondwords_customize'
					);
					if (
						! customize ||
						! customize.checked ||
						language.value
					) {
						toggleLoader( false );
						return;
					}

					const lang = project && project.language;
					if ( lang ) {
						language.value = lang;
						// Populate the language's voices but seed no voice.
						this.getVoices( lang, '' );
					} else {
						toggleLoader( false );
					}
				} )
				.catch( () => {
					toggleLoader( false );
				} );
		},

		/**
		 * Get voices for a language, then rebuild the Voice + Model dropdowns and
		 * pre-select the language's default voice.
		 *
		 * @param {string} languageCode   The language code.
		 * @param {string} defaultVoiceId The language's default body voice id.
		 */
		getVoices( languageCode, defaultVoiceId ) {
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

			// Serialise concurrent fetches — only the latest one applies.
			const reqId = ++this.voicesReq;

			const endpoint = `${ data.root }beyondwords/v1/languages/${ languageCode }/voices`;

			window
				.fetch( endpoint, {
					method: 'GET',
					headers: { 'X-WP-Nonce': data.nonce },
				} )
				.then( ( response ) => {
					// window.fetch does not reject on HTTP error statuses. A
					// WordPress REST error (e.g. an expired nonce returning 403)
					// resolves with a JSON error *object*, not a voices array,
					// so surface it through the catch below rather than letting
					// that object reach renderVoiceNames().
					if ( ! response.ok ) {
						return response
							.json()
							.catch( () => null )
							.then( ( body ) => {
								throw new Error(
									( body && body.message ) ||
										`HTTP ${ response.status }`
								);
							} );
					}
					return response.json();
				} )
				.then( ( voices ) => {
					if ( reqId !== this.voicesReq ) {
						return;
					}
					this.voices = Array.isArray( voices ) ? voices : [];
					this.renderVoiceNames( defaultVoiceId );
				} )
				.catch( ( error ) => {
					if ( reqId !== this.voicesReq ) {
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
					if ( reqId === this.voicesReq ) {
						toggleLoader( false );
					}
				} );
		},

		/**
		 * Rebuild the Voice (name) dropdown from the current voices list and
		 * pre-select the language's default voice, so a concrete voice is always
		 * set. Languages with no default voice fall back to "Select a voice".
		 *
		 * @param {string} defaultVoiceId The language's default body voice id.
		 */
		renderVoiceNames( defaultVoiceId ) {
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

			let selectedName = '';
			if ( defaultVoiceId ) {
				const defaultVoice = this.voices.find(
					( voice ) => String( voice.id ) === String( defaultVoiceId )
				);
				if ( defaultVoice ) {
					selectedName = defaultVoice.name;
				}
			}

			voiceSelect.replaceChildren(
				option( '', SELECT_VOICE ),
				...names.map( ( name ) => option( name, name ) )
			);
			voiceSelect.value = selectedName;
			voiceSelect.disabled = false;
			voiceSelect.style.display = '';

			if ( selectedName ) {
				this.setVoiceName( selectedName );
				const modelSelect = document.getElementById(
					'beyondwords_voice_id'
				);
				if ( modelSelect && defaultVoiceId ) {
					modelSelect.value = String( defaultVoiceId );
				}
			} else {
				this.setModelOptions( [] );
			}
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
