/* global beyondwordsData */

/*
 * Classic-editor Customize + Language + Model + Voice behaviour. Mirrors the
 * block editor's voice-section.js + helpers.js. The selects live in the post
 * <form> and submit on save, so no autosave/Heartbeat hook is needed.
 */
( function () {
	'use strict';

	const data =
		typeof beyondwordsData !== 'undefined' ? beyondwordsData : null;

	const ELEVENLABS = ( data && data.elevenLabs ) || 'ElevenLabs';
	const DEFAULT_MODEL_ID =
		( data && data.defaultModelId ) || 'eleven_multilingual_v2';
	const SELECT_VOICE = ( data && data.selectVoice ) || 'Select a voice';
	const SELECT_MODEL = ( data && data.selectModel ) || 'Select a model';
	const STANDARD_MODEL = ( data && data.standardModel ) || 'Legacy';
	const MODEL_LABELS = ( data && data.voiceModelLabels ) || {};

	// Bucket key for voices without an ElevenLabs model_id (e.g. standard voices).
	const STANDARD_MODEL_KEY = 'standard';

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
	 * The model bucket key for a voice: its ElevenLabs model_id, else Standard.
	 *
	 * @param {Object} voice A voice record.
	 *
	 * @return {string} The model bucket key.
	 */
	const voiceModelKey = ( voice ) => {
		if (
			voice &&
			voice.service === ELEVENLABS &&
			typeof voice.model_id === 'string'
		) {
			return voice.model_id;
		}
		return STANDARD_MODEL_KEY;
	};

	/**
	 * The distinct model buckets across a language's voices, for the Model dropdown.
	 *
	 * @param {Array<Object>} voices All voices for the current language.
	 *
	 * @return {Array<{key: string, label: string}>} The Model dropdown options.
	 */
	const languageModels = ( voices ) => {
		const modelIds = [];
		let hasStandard = false;

		( voices || [] ).forEach( ( voice ) => {
			const key = voiceModelKey( voice );
			if ( key === STANDARD_MODEL_KEY ) {
				hasStandard = true;
			} else if ( ! modelIds.includes( key ) ) {
				modelIds.push( key );
			}
		} );

		modelIds.sort( ( a, b ) => {
			if ( a === DEFAULT_MODEL_ID ) {
				return -1;
			}
			if ( b === DEFAULT_MODEL_ID ) {
				return 1;
			}
			return 0;
		} );

		const models = modelIds.map( ( key ) => ( {
			key,
			label: modelLabel( key ),
		} ) );

		if ( hasStandard ) {
			models.push( { key: STANDARD_MODEL_KEY, label: STANDARD_MODEL } );
		}

		return models;
	};

	const option = ( value, text ) => {
		const el = document.createElement( 'option' );
		el.value = value;
		el.textContent = text;
		return el;
	};

	const byId = ( id ) => document.getElementById( id );

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

			const customize = byId( 'beyondwords_customize' );
			const language = byId( 'beyondwords_language_code' );
			const model = byId( 'beyondwords_model' );

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

			if ( model ) {
				model.addEventListener( 'change', ( event ) => {
					this.onModelChange( event.target.value );
				} );
			}

			// Seed this.voices so the Model filter has data before the user interacts.
			this.hydrate();
		},

		/**
		 * Show/hide the language/model/voice fields.
		 *
		 * Customize off clears the selects so they submit empty and save()
		 * removes the meta, reverting the post to the project defaults.
		 *
		 * @param {boolean} on Whether Customize is enabled.
		 */
		toggleCustomize( on ) {
			const fields = byId( 'beyondwords-metabox-select-voice--fields' );
			if ( fields ) {
				fields.style.display = on ? '' : 'none';
			}

			if ( on ) {
				this.applyProjectDefaultLanguage();
				return;
			}

			const language = byId( 'beyondwords_language_code' );
			if ( language ) {
				language.value = '';
			}

			this.voices = [];
			this.renderModels( '' );
		},

		/**
		 * On Customize-on, fetch the project's default language and pre-select it.
		 *
		 * Only the language is seeded — the user picks the Model + Voice; on
		 * failure we fall back to the manual "pick a language" flow.
		 */
		applyProjectDefaultLanguage() {
			const language = byId( 'beyondwords_language_code' );

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
					// Bail if Customize was switched off, or a language chosen, while
					// in flight — else we'd persist a language on an un-customised post.
					const customize = byId( 'beyondwords_customize' );
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
						// Populate the language's models/voices but seed no voice.
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
		 * Hydrate this.voices for an already-customized saved post.
		 *
		 * Without it the first Model change runs against empty state and save()
		 * drops the stored voice. Loads without clearing, so the server-rendered
		 * dropdowns stay put until the fetch re-renders the same selection.
		 */
		hydrate() {
			const customize = byId( 'beyondwords_customize' );
			const language = byId( 'beyondwords_language_code' );

			// Only a saved customized post needs hydrating; a fresh one fetches on demand.
			if (
				! customize ||
				! customize.checked ||
				! language ||
				! language.value
			) {
				return;
			}

			const voiceSelect = byId( 'beyondwords_voice_id' );
			const savedVoiceId = voiceSelect ? voiceSelect.value : '';

			// Disable the Model filter until voices load so a change can't run
			// against empty state; it carries no name, so submits are unaffected.
			const modelSelect = byId( 'beyondwords_model' );
			if ( modelSelect ) {
				modelSelect.disabled = true;
			}

			this.loadVoices( language.value )
				.then( ( applied ) => {
					if ( applied ) {
						this.renderModels( savedVoiceId );
					}
				} )
				.finally( () => {
					if ( modelSelect ) {
						modelSelect.disabled = false;
					}
				} );
		},

		/**
		 * Get voices for a language, then rebuild the Model + Voice dropdowns.
		 *
		 * A supplied default voice pre-selects its model and voice; otherwise the
		 * Model opens on its placeholder and Voice stays hidden until one is picked.
		 *
		 * @param {string} languageCode   The language code.
		 * @param {string} defaultVoiceId The language's default body voice id.
		 */
		getVoices( languageCode, defaultVoiceId ) {
			// Clear the stale UI while the new language's voices resolve.
			this.voices = [];
			this.renderModels( '' );

			this.loadVoices( languageCode ).then( ( applied ) => {
				if ( applied ) {
					this.renderModels( defaultVoiceId );
				}
			} );
		},

		/**
		 * Fetch a language's voices into this.voices. Does not render.
		 *
		 * Resolves true when this (latest) fetch applied; false when superseded,
		 * on error, or when no language is given.
		 *
		 * @param {string} languageCode The language code.
		 *
		 * @return {Promise<boolean>} Whether this fetch applied.
		 */
		loadVoices( languageCode ) {
			toggleLoader( true );

			if ( ! languageCode ) {
				toggleLoader( false );
				return Promise.resolve( false );
			}

			// Serialise concurrent fetches — only the latest one applies.
			const reqId = ++this.voicesReq;

			const endpoint = `${ data.root }beyondwords/v1/languages/${ languageCode }/voices`;

			return window
				.fetch( endpoint, {
					method: 'GET',
					headers: { 'X-WP-Nonce': data.nonce },
				} )
				.then( ( response ) => {
					// fetch doesn't reject on HTTP errors; a REST error (e.g. expired
					// nonce → 403) resolves with a JSON object, so throw into the catch.
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
						return false;
					}
					this.voices = Array.isArray( voices ) ? voices : [];
					return true;
				} )
				.catch( ( error ) => {
					if ( reqId !== this.voicesReq ) {
						return false;
					}
					// eslint-disable-next-line no-console
					console.log( '🔊 Unable to load voices', error );
					this.voices = [];
					return false;
				} )
				.finally( () => {
					if ( reqId === this.voicesReq ) {
						toggleLoader( false );
					}
				} );
		},

		/**
		 * Rebuild the Model dropdown from the current voices, then the Voice dropdown.
		 *
		 * The Model dropdown is hidden when a language offers a single bucket.
		 *
		 * @param {string} selectedVoiceId The voice id to pre-select, or ''.
		 */
		renderModels( selectedVoiceId ) {
			const modelWrapper = byId(
				'beyondwords-metabox-select-voice--model'
			);
			const modelSelect = byId( 'beyondwords_model' );

			const models = languageModels( this.voices );
			const showModel = models.length > 1;

			if ( modelSelect ) {
				modelSelect.replaceChildren(
					option( '', SELECT_MODEL ),
					...models.map( ( model ) =>
						option( model.key, model.label )
					)
				);
			}

			const selectedVoice = selectedVoiceId
				? this.voices.find(
						( voice ) =>
							String( voice.id ) === String( selectedVoiceId )
				  )
				: null;
			const selectedKey = selectedVoice
				? voiceModelKey( selectedVoice )
				: '';

			if ( modelSelect ) {
				modelSelect.value = showModel ? selectedKey : '';
			}
			if ( modelWrapper ) {
				modelWrapper.style.display = showModel ? '' : 'none';
			}

			this.renderVoices( selectedKey, selectedVoiceId, showModel );
		},

		/**
		 * Rebuild the Voice dropdown for a model bucket and select a voice.
		 *
		 * Hidden while gated with no model chosen; a single bucket lists every voice.
		 *
		 * @param {string}  modelKey    The selected model bucket key, or ''.
		 * @param {string}  preselectId The voice id to select if in the bucket.
		 * @param {boolean} showModel   Whether the Model dropdown is shown.
		 */
		renderVoices( modelKey, preselectId, showModel ) {
			const voiceWrapper = byId(
				'beyondwords-metabox-select-voice--voice-id'
			);
			const voiceSelect = byId( 'beyondwords_voice_id' );

			if ( showModel && '' === modelKey ) {
				if ( voiceSelect ) {
					voiceSelect.replaceChildren( option( '', SELECT_VOICE ) );
					voiceSelect.value = '';
				}
				if ( voiceWrapper ) {
					voiceWrapper.style.display = 'none';
				}
				return;
			}

			const bucketVoices = showModel
				? this.voices.filter(
						( voice ) => voiceModelKey( voice ) === modelKey
				  )
				: this.voices;

			if ( voiceSelect ) {
				voiceSelect.replaceChildren(
					option( '', SELECT_VOICE ),
					...bucketVoices.map( ( voice ) =>
						option( String( voice.id ), voice.name )
					)
				);

				const inBucket = bucketVoices.some(
					( voice ) => String( voice.id ) === String( preselectId )
				);
				voiceSelect.value = inBucket ? String( preselectId ) : '';
			}

			if ( voiceWrapper ) {
				voiceWrapper.style.display = bucketVoices.length ? '' : 'none';
			}
		},

		/**
		 * Pick a model: list that bucket's voices and select the first.
		 *
		 * A concrete voice id is always submitted (the voice carries the model).
		 *
		 * @param {string} modelKey The selected model bucket key.
		 */
		onModelChange( modelKey ) {
			if ( ! modelKey ) {
				this.renderVoices( '', '', true );
				return;
			}
			const first = this.voices.find(
				( voice ) => voiceModelKey( voice ) === modelKey
			);
			this.renderVoices(
				modelKey,
				first ? String( first.id ) : '',
				true
			);
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
