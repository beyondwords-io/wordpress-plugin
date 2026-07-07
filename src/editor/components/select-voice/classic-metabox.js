/* global beyondwordsData */

/*
 * Classic-editor Customize + Language + Accent + Model + Voice behaviour.
 *
 * "Customize" is opt-in. While off, the post uses the project default language
 * and voice: the fields stay hidden and the selects submit empty so save()
 * removes the meta. While on, the Language dropdown drives a voices fetch and
 * seeds the language's default voice (we never send the language itself — the
 * voice carries it).
 *
 * "Language" lists language NAMES (e.g. English); "Accent" lists the accents
 * for the chosen name and is the submitted field (`beyondwords_language_code`)
 * — a (name, accent) pair maps to exactly one language code. Picking a name
 * auto-selects its first accent, which fetches that language's voices and
 * seeds its default body voice. The Accent select is hidden when a language
 * offers a single accent (nothing to choose).
 *
 * "Model" is a language-level filter: each ElevenLabs model_id, plus a single
 * "Standard" bucket for non-ElevenLabs voices. Picking a model narrows the
 * Voice dropdown to the voices that offer it. The Voice dropdown is the saved
 * field (`beyondwords_voice_id`) — it submits the chosen voice id, which carries
 * the model. When a language offers a single bucket the Model dropdown is hidden
 * and every voice is listed. Mirrors the block editor's voice-section.js +
 * helpers.js.
 *
 * Vanilla JS — no jQuery dependency. The selects live in the post <form>, so
 * their values submit on save; no autosave/Heartbeat hook is needed.
 */
( function () {
	'use strict';

	const data =
		typeof beyondwordsData !== 'undefined' ? beyondwordsData : null;

	const LANGUAGES = ( data && data.languages ) || [];
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
	 * The model bucket key for a voice: its ElevenLabs model_id, or the shared
	 * Standard bucket for any other service.
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
	 * The distinct model buckets across a language's voices, ElevenLabs models
	 * first (the default leading), then a single Standard bucket if present.
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

	/**
	 * The language rows (accents) for a language name, in API order.
	 *
	 * @param {string} name The language name, or ''.
	 *
	 * @return {Array<Object>} The matching slim language rows.
	 */
	const accentsForName = ( name ) =>
		name ? LANGUAGES.filter( ( language ) => language.name === name ) : [];

	/**
	 * Find a slim language row by its code.
	 *
	 * @param {string} code The language code.
	 *
	 * @return {Object|null} The matching row, or null.
	 */
	const findLanguageByCode = ( code ) =>
		LANGUAGES.find(
			( language ) => String( language.code ) === String( code )
		) || null;

	/**
	 * An Accent <option> for a language row: the CODE as its value, carrying
	 * the language's default body voice id for the voice-seeding flow.
	 *
	 * @param {Object} language A slim language row.
	 *
	 * @return {HTMLOptionElement} The option element.
	 */
	const accentOption = ( language ) => {
		const el = option( String( language.code ), language.accent );
		el.setAttribute(
			'data-default-voice-id',
			language.defaultVoiceId ? String( language.defaultVoiceId ) : ''
		);
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

			const customize = byId( 'beyondwords_customize' );
			const languageName = byId( 'beyondwords_language_name' );
			const language = byId( 'beyondwords_language_code' );
			const model = byId( 'beyondwords_model' );

			if ( customize ) {
				customize.addEventListener( 'change', ( event ) => {
					this.toggleCustomize( event.target.checked );
				} );
			}

			if ( languageName ) {
				languageName.addEventListener( 'change', ( event ) => {
					this.onLanguageNameChange( event.target.value );
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

			// A saved customized post is server-rendered with its Model + Voice
			// dropdowns populated, but this.voices starts empty; hydrate it so the
			// Model filter has data before the user interacts.
			this.hydrate();
		},

		/**
		 * Show/hide the language/model/voice fields. Turning Customize off reverts
		 * the post to the project defaults by clearing the selects, so they submit
		 * empty and save() removes the meta.
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

			const languageName = byId( 'beyondwords_language_name' );
			if ( languageName ) {
				languageName.value = '';
			}

			// Resets the Accent select to a single empty option, so it submits
			// empty and save() removes the meta.
			this.renderAccents( '', '' );

			this.voices = [];
			this.renderModels( '' );
		},

		/**
		 * Pick a language NAME → rebuild the Accent select and auto-select its
		 * first accent, which resolves the stored language code: fetch that
		 * language's voices and seed its default body voice. The placeholder
		 * clears the selection (the Accent select submits empty).
		 *
		 * @param {string} name The language name, or '' for the placeholder.
		 */
		onLanguageNameChange( name ) {
			const first = this.renderAccents( name, '' );

			if ( first ) {
				this.getVoices(
					String( first.code ),
					first.defaultVoiceId ? String( first.defaultVoiceId ) : ''
				);
			} else {
				this.getVoices( '', '' );
			}
		},

		/**
		 * Rebuild the Accent select for a language name and select an accent —
		 * the row matching selectedCode, falling back to the name's first
		 * accent. The wrapper is hidden when the language offers a single
		 * accent (nothing to choose); the select still submits that accent's
		 * code. With no name, a single empty option keeps the field posting
		 * ('' = no language chosen).
		 *
		 * @param {string} name         The language name, or ''.
		 * @param {string} selectedCode The language code to select, or ''.
		 *
		 * @return {Object|null} The selected slim language row, or null.
		 */
		renderAccents( name, selectedCode ) {
			const wrapper = byId( 'beyondwords-metabox-select-voice--accent' );
			const accentSelect = byId( 'beyondwords_language_code' );
			const accents = accentsForName( name );

			let selected = null;

			if ( accents.length ) {
				selected =
					accents.find(
						( language ) =>
							String( language.code ) === String( selectedCode )
					) || accents[ 0 ];
			}

			if ( accentSelect ) {
				if ( selected ) {
					accentSelect.replaceChildren(
						...accents.map( accentOption )
					);
					accentSelect.value = String( selected.code );
				} else {
					accentSelect.replaceChildren( option( '', '' ) );
					accentSelect.value = '';
				}
			}

			if ( wrapper ) {
				wrapper.style.display = accents.length > 1 ? '' : 'none';
			}

			return selected;
		},

		/**
		 * Programmatically select a language code: set the Language (name)
		 * select, rebuild its Accent options with the code selected, and fetch
		 * the voices. Used by the project-default flow.
		 *
		 * @param {string} code        The language code.
		 * @param {string} seedVoiceId The voice id to seed, or '' for none.
		 *
		 * @return {boolean} Whether the code matched a known language.
		 */
		selectCode( code, seedVoiceId ) {
			const row = findLanguageByCode( code );

			if ( ! row ) {
				return false;
			}

			const nameSelect = byId( 'beyondwords_language_name' );
			if ( nameSelect ) {
				nameSelect.value = row.name;
			}

			this.renderAccents( row.name, String( code ) );
			this.getVoices( String( code ), seedVoiceId || '' );

			return true;
		},

		/**
		 * On Customize-on, fetch the project's default language and pre-select it
		 * — only the language; the user picks the Model + Voice. A spinner shows
		 * while the language and its voices resolve. On failure, or when the post
		 * already has a language, fall back to the manual "pick a language" flow.
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
					// Bail if Customize was switched off, or a language was
					// chosen, while the request was in flight — otherwise we'd
					// re-show the fields and persist a language on a post the
					// user left un-customised.
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
					// Select the name + accent for the default code and
					// populate the language's models/voices, seeding no voice.
					if ( ! lang || ! this.selectCode( String( lang ), '' ) ) {
						toggleLoader( false );
					}
				} )
				.catch( () => {
					toggleLoader( false );
				} );
		},

		/**
		 * Hydrate this.voices for an already-customized saved post so the Model
		 * filter has data before the user interacts. The PHP element() server-
		 * renders the correct Model + Voice dropdowns, but this.voices starts
		 * empty; without this the first Model change finds no voices, empties the
		 * Voice select, and save() then drops the stored voice. Loads WITHOUT
		 * clearing first, so the correct server-rendered dropdowns stay put until
		 * the fetch resolves and re-renders to the same selection.
		 */
		hydrate() {
			const customize = byId( 'beyondwords_customize' );
			const language = byId( 'beyondwords_language_code' );

			// A fresh, un-customized post has no language yet; toggling Customize
			// fetches on demand. Only a saved customized post needs hydrating.
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

			// Disable the Model filter until the in-memory voices load, so a Model
			// change during the fetch can't run against empty state (which would
			// blank the Voice select). The Model select carries no name, so
			// disabling it has no effect on what the form submits.
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
		 * When a default voice id is supplied (the language's default body voice,
		 * set when the user picks a language) its model is pre-selected and the
		 * voice chosen; otherwise the Model dropdown opens on its placeholder and
		 * the Voice dropdown stays hidden until a model is picked.
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
		 * Fetch a language's voices into this.voices, serialising concurrent
		 * fetches so only the latest applies. Does not render — the caller decides
		 * how. Resolves true when this (latest) fetch succeeded and this.voices was
		 * updated; false when superseded, on error, or when no language is given.
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
					// window.fetch does not reject on HTTP error statuses. A
					// WordPress REST error (e.g. an expired nonce returning 403)
					// resolves with a JSON error *object*, not a voices array,
					// so surface it through the catch below.
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
		 * Rebuild the Model dropdown from the current voices and select the model
		 * for the supplied voice (if any), then rebuild the Voice dropdown for
		 * that model. The Model dropdown is hidden when a language offers a single
		 * bucket.
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
		 * Rebuild the Voice dropdown for a model bucket and select a voice. With a
		 * Model gate and no model chosen, the Voice dropdown is hidden; with a
		 * single bucket every voice is listed.
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

			// Gated and no model chosen yet → hide the (empty) Voice dropdown.
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
		 * Pick a model → list that bucket's voices and select the first, so a
		 * concrete voice id is always submitted (the voice carries the model).
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
