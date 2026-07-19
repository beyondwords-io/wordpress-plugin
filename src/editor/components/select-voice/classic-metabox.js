/* global beyondwordsData */

/*
 * Classic-editor Customize + Language + Accent + Model + Voice behaviour. Mirrors
 * the block editor's voice-section.js + helpers.js. Language lists names while
 * Accent carries the submitted language CODE. The selects live in the post
 * <form> and submit on save, so no autosave/Heartbeat hook is needed.
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
	 * A voice's primary (native) language code.
	 *
	 * @param {Object} voice A voice record.
	 *
	 * @return {string} The primary language code, or ''.
	 */
	const voicePrimaryCode = ( voice ) => {
		const language = voice && voice.language;
		if ( typeof language === 'string' ) {
			return language;
		}
		if ( language && typeof language === 'object' && language.code ) {
			return language.code;
		}
		return (
			( voice && voice.languages && voice.languages[ 0 ]
				? voice.languages[ 0 ].code
				: '' ) || ''
		);
	};

	/**
	 * Whether a voice is native to a language code. A voice with no
	 * determinable primary language is treated as native, so it is never hidden.
	 *
	 * @param {Object} voice A voice record.
	 * @param {string} code  The language code.
	 *
	 * @return {boolean} Whether the voice is native to the code.
	 */
	const voiceIsNative = ( voice, code ) => {
		const primary = voicePrimaryCode( voice );
		if ( ! primary ) {
			return true;
		}
		return String( primary ) === String( code );
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
			const native = byId( 'beyondwords_native' );
			const model = byId( 'beyondwords_model' );

			if ( customize ) {
				customize.addEventListener( 'change', ( event ) => {
					this.toggleCustomize( event.target.checked );
				} );
			}

			if ( native ) {
				native.addEventListener( 'change', () => {
					const voiceSelect = byId( 'beyondwords_voice_id' );
					this.renderModels( voiceSelect ? voiceSelect.value : '' );
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

			const languageName = byId( 'beyondwords_language_name' );
			if ( languageName ) {
				languageName.value = '';
			}

			// Leaves a single empty option, so it submits '' and save() removes the meta.
			this.renderAccents( '', '' );

			this.voices = [];
			this.renderModels( '' );
		},

		/**
		 * Pick a language NAME → rebuild the Accent select, auto-select its
		 * first accent and seed that language's default body voice.
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
		 * Rebuild the Accent select for a language name, selecting selectedCode
		 * or the first accent. The wrapper is hidden for a single accent, but
		 * the select stays mounted so it still submits that accent's code.
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
		 * Programmatically select a language code across the Language + Accent
		 * selects and fetch its voices.
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
					if ( ! lang || ! this.selectCode( String( lang ), '' ) ) {
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
		 * The current voices narrowed by the Native filter. keepId is always
		 * kept, so toggling the filter never drops the current selection.
		 *
		 * @param {string} keepId The voice id to always keep, or ''.
		 *
		 * @return {Array<Object>} The native-scoped voices.
		 */
		scopedVoices( keepId ) {
			const nativeSelect = byId( 'beyondwords_native' );
			const codeSelect = byId( 'beyondwords_language_code' );
			const nativeFilter = nativeSelect ? nativeSelect.value : 'native';
			const code = codeSelect ? codeSelect.value : '';

			let result =
				nativeFilter === 'all'
					? this.voices
					: this.voices.filter( ( voice ) =>
							voiceIsNative( voice, code )
					  );

			if (
				keepId &&
				! result.some(
					( voice ) => String( voice.id ) === String( keepId )
				)
			) {
				const saved = this.voices.find(
					( voice ) => String( voice.id ) === String( keepId )
				);
				if ( saved ) {
					result = result.concat( [ saved ] );
				}
			}

			return result;
		},

		/**
		 * Rebuild the Model dropdown from the native-scoped voices, then the Voice
		 * dropdown. The Model dropdown is hidden when the scoped set offers one bucket.
		 *
		 * @param {string} selectedVoiceId The voice id to pre-select, or ''.
		 */
		renderModels( selectedVoiceId ) {
			const modelWrapper = byId(
				'beyondwords-metabox-select-voice--model'
			);
			const modelSelect = byId( 'beyondwords_model' );

			const voices = this.scopedVoices( selectedVoiceId );
			const models = languageModels( voices );
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
				? voices.find(
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

			this.renderVoices(
				selectedKey,
				selectedVoiceId,
				showModel,
				voices
			);
		},

		/**
		 * Rebuild the Voice dropdown for a model bucket and select a voice.
		 *
		 * Hidden while gated with no model chosen; a single bucket lists every voice.
		 *
		 * @param {string}        modelKey    The selected model bucket key, or ''.
		 * @param {string}        preselectId The voice id to select if in the bucket.
		 * @param {boolean}       showModel   Whether the Model dropdown is shown.
		 * @param {Array<Object>} voices      The native-scoped voices to list.
		 */
		renderVoices( modelKey, preselectId, showModel, voices ) {
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
				? voices.filter(
						( voice ) => voiceModelKey( voice ) === modelKey
				  )
				: voices;

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
			const voiceSelect = byId( 'beyondwords_voice_id' );
			const voices = this.scopedVoices(
				voiceSelect ? voiceSelect.value : ''
			);

			if ( ! modelKey ) {
				this.renderVoices( '', '', true, voices );
				return;
			}
			const first = voices.find(
				( voice ) => voiceModelKey( voice ) === modelKey
			);
			this.renderVoices(
				modelKey,
				first ? String( first.id ) : '',
				true,
				voices
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
