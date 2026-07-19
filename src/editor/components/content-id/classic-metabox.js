/* global beyondwordsData, BeyondWords */

( function () {
	'use strict';

	/**
	 * Content statuses that mean "still processing" — keep polling.
	 *
	 * @type {string[]}
	 */
	const NON_TERMINAL_STATUSES = [ 'draft', 'queued', 'processing' ];

	/**
	 * Poll `fetchStatus` until the content reaches a terminal status.
	 *
	 * Mirror of src/editor/lib/poll-content-status.js (which the block editor
	 * imports and jest covers). Inlined here because this classic-editor script
	 * is enqueued raw — it is not built, so it cannot `import`. Resolves
	 * { status, timedOut }; a cancelled poll (superseded by a newer one) simply
	 * stops without resolving.
	 *
	 * @param {Object}   options               Options.
	 * @param {Function} options.fetchStatus   () => Promise<{ status }>.
	 * @param {Function} [options.onTick]      Called per non-terminal poll.
	 * @param {Function} [options.isHidden]    () => boolean; skip the call when true.
	 * @param {Function} [options.isCancelled] () => boolean; stop polling when true.
	 * @param {number}   [options.intervalMs]  Delay between polls.
	 * @param {number}   [options.timeoutMs]   Overall time budget.
	 * @return {Promise<{status: (string|undefined), timedOut: boolean}>} Result.
	 */
	function pollContentStatus( options ) {
		const fetchStatus = options.fetchStatus;
		const onTick = options.onTick;
		const isHidden = options.isHidden;
		const isCancelled =
			options.isCancelled ||
			function () {
				return false;
			};
		const intervalMs = options.intervalMs || 3000;
		const timeoutMs = options.timeoutMs || 120000;
		const start = Date.now();
		let lastStatus;
		let hiddenMs = 0;

		return new Promise( function ( resolve ) {
			function tick() {
				if ( isCancelled() ) {
					return;
				}

				// Budget measures visible time — a hidden tab resumes polling on
				// return instead of timing out having never fetched.
				if ( Date.now() - start - hiddenMs >= timeoutMs ) {
					resolve( { status: lastStatus, timedOut: true } );
					return;
				}

				// Skip the upstream call while the tab is hidden — each poll is
				// an uncached upstream API call.
				if ( isHidden && isHidden() ) {
					const hiddenAt = Date.now();
					setTimeout( function () {
						hiddenMs += Date.now() - hiddenAt;
						tick();
					}, intervalMs );
					return;
				}

				fetchStatus()
					.then( function ( result ) {
						if ( isCancelled() ) {
							return;
						}

						const status = result && result.status;
						lastStatus = status;

						if ( NON_TERMINAL_STATUSES.indexOf( status ) === -1 ) {
							resolve( { status, timedOut: false } );
							return;
						}

						if ( onTick ) {
							onTick( status );
						}

						setTimeout( tick, intervalMs );
					} )
					.catch( function () {
						if ( isCancelled() ) {
							return;
						}

						// Transient failure — keep polling until the budget.
						setTimeout( tick, intervalMs );
					} );
			}

			tick();
		} );
	}

	/**
	 * Resolve once the BeyondWords player SDK global is available.
	 *
	 * The SDK loads from a deferred <script>; by the time polling finishes it is
	 * almost always ready, but guard with a short bounded wait just in case.
	 *
	 * @return {Promise<void>} Resolves when ready (or after the wait budget).
	 */
	function whenBeyondWordsReady() {
		return new Promise( function ( resolve ) {
			function ready() {
				return (
					typeof BeyondWords !== 'undefined' &&
					typeof BeyondWords.Player === 'function'
				);
			}

			if ( ready() ) {
				resolve();
				return;
			}

			let attempts = 0;
			const id = setInterval( function () {
				attempts += 1;
				if ( ready() || attempts > 100 ) {
					clearInterval( id );
					resolve();
				}
			}, 100 );
		} );
	}

	/**
	 * Render the spinner + loading text into the player container.
	 *
	 * @param {HTMLElement} container The player container.
	 */
	function showMetaboxLoading( container ) {
		container.innerHTML = '';

		const spinner = document.createElement( 'span' );
		spinner.className = 'spinner is-active';
		spinner.style.float = 'none';
		spinner.style.margin = '0 8px 0 0';

		const text = document.createElement( 'span' );
		text.className = 'beyondwords-player-loading-text';
		text.textContent = wp.i18n.__( 'Generating…', 'speechkit' );

		container.appendChild( spinner );
		container.appendChild( text );
	}

	/**
	 * Replace the container contents with a terminal message (error / skipped /
	 * timeout), or clear it when there is nothing to say.
	 *
	 * @param {HTMLElement} container The player container.
	 * @param {Object}      result    The poll result { status, timedOut }.
	 */
	function showMetaboxMessage( container, result ) {
		let message = '';

		if ( result.timedOut ) {
			message = wp.i18n.__(
				'Generation is taking longer than expected. Refresh to check again.',
				'speechkit'
			);
		} else if ( result.status === 'error' ) {
			message = wp.i18n.__( 'Generation failed.', 'speechkit' );
		} else if ( result.status === 'skipped' ) {
			message = wp.i18n.__( 'No content was generated.', 'speechkit' );
		}

		container.innerHTML = '';

		if ( message ) {
			const p = document.createElement( 'p' );
			p.className = 'beyondwords-player-message';
			p.textContent = message;
			container.appendChild( p );
		}
	}

	/**
	 * Poll the content status, then embed the player once it is `processed`.
	 *
	 * Reads projectId / contentId / previewToken from the container's data-*
	 * attributes, shows a spinner while the content is still processing, and only
	 * constructs the player on `processed` — so the player never requests (and
	 * CDN-caches a 404 for) unprocessed content. A terminal error / skipped /
	 * timeout shows a short message instead.
	 *
	 * @param {HTMLElement} container The #beyondwords-metabox-player element.
	 */
	function initMetaboxPlayer( container ) {
		if ( ! container ) {
			return;
		}

		if (
			typeof beyondwordsData === 'undefined' ||
			! beyondwordsData.root
		) {
			return;
		}

		const projectId = container.getAttribute( 'data-project-id' );
		const contentId = container.getAttribute( 'data-content-id' );
		const previewToken =
			container.getAttribute( 'data-preview-token' ) || '';

		if ( ! projectId || ! contentId ) {
			return;
		}

		// A newer init for this container supersedes any in-flight poll.
		const token = {};
		container.beyondwordsPollToken = token;
		const isCancelled = function () {
			return container.beyondwordsPollToken !== token;
		};

		function embedPlayer() {
			whenBeyondWordsReady().then( function () {
				if ( isCancelled() ) {
					return;
				}

				if (
					typeof BeyondWords === 'undefined' ||
					typeof BeyondWords.Player !== 'function'
				) {
					// SDK unavailable — either it was never emitted on this page
					// (the post had no content at load, so player_embed() didn't
					// run) or it failed to load within the bounded wait.
					// Generation itself succeeded; clear the spinner so it isn't
					// left stuck. A page refresh re-loads the SDK.
					container.innerHTML = '';
					return;
				}

				container.innerHTML = '';

				// The SDK constructor can throw; contain it so a preview-only
				// error can't surface as a fatal.
				try {
					new BeyondWords.Player( {
						target: container,
						projectId: Number( projectId ),
						contentId,
						previewToken,
						adverts: [],
						analyticsConsent: 'none',
						introsOutros: [],
						playerStyle: 'small',
						widgetStyle: 'none',
					} );
				} catch {
					// Preview failed to initialise; saved content is intact.
				}
			} );
		}

		showMetaboxLoading( container );

		pollContentStatus( {
			fetchStatus() {
				return fetch(
					beyondwordsData.root +
						'beyondwords/v1/projects/' +
						encodeURIComponent( projectId ) +
						'/content/' +
						encodeURIComponent( contentId ),
					{
						credentials: 'same-origin',
						headers: {
							'X-WP-Nonce': beyondwordsData.nonce,
						},
					}
				)
					.then( function ( response ) {
						if ( ! response.ok ) {
							throw new Error( response.statusText );
						}
						return response.json();
					} )
					.then( function ( data ) {
						return { status: data.status };
					} );
			},
			isHidden() {
				return document.hidden;
			},
			isCancelled,
		} ).then( function ( result ) {
			if ( isCancelled() ) {
				return;
			}

			if ( ! result.timedOut && result.status === 'processed' ) {
				embedPlayer();
			} else {
				showMetaboxMessage( container, result );
			}
		} );
	}

	/**
	 * Remove any existing notice from the metabox.
	 */
	function clearNotice() {
		const container = document.getElementById(
			'beyondwords-metabox-content-id'
		);
		if ( ! container ) {
			return;
		}

		const existing = container.querySelector(
			'.beyondwords-content-id-notice'
		);
		if ( existing ) {
			existing.remove();
		}
	}

	/**
	 * Show a dismissible notice inside the metabox.
	 *
	 * @param {string} message Notice text.
	 * @param {string} type    'success' or 'error'.
	 */
	function showNotice( message, type ) {
		clearNotice();

		const container = document.getElementById(
			'beyondwords-metabox-content-id'
		);
		if ( ! container ) {
			return;
		}

		const notice = document.createElement( 'div' );
		notice.className =
			'beyondwords-content-id-notice ' +
			( type === 'error' ? 'beyondwords-error' : 'beyondwords-success' );
		const p = document.createElement( 'p' );
		p.textContent = message;
		notice.appendChild( p );

		container.appendChild( notice );
	}

	/**
	 * Build the REST base for the current post type.
	 *
	 * Prefers the data-rest-base attribute set by PHP; falls back to a
	 * simple mapping for core post types.
	 *
	 * @param {HTMLElement} button The fetch button element.
	 * @return {string} The REST base slug.
	 */
	function getRestBase( button ) {
		const base = button.getAttribute( 'data-rest-base' );
		if ( base ) {
			return base;
		}

		const postTypeInput = document.getElementById( 'post_type' );
		const postType = ( postTypeInput && postTypeInput.value ) || 'post';

		if ( postType === 'post' ) {
			return 'posts';
		}
		if ( postType === 'page' ) {
			return 'pages';
		}
		return postType;
	}

	/**
	 * Save meta to the post via the WP REST API.
	 *
	 * @param {string} restBase REST base for the post type.
	 * @param {string} postId   The post ID.
	 * @param {Object} meta     Meta key/value pairs.
	 * @return {Promise} Resolves with the fetch Response.
	 */
	function savePostMeta( restBase, postId, meta ) {
		return fetch(
			beyondwordsData.root + 'wp/v2/' + restBase + '/' + postId,
			{
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': beyondwordsData.nonce,
				},
				body: JSON.stringify( { meta } ),
			}
		).then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( 'Failed to save' );
			}
			return response;
		} );
	}

	/**
	 * Update visible metabox form controls to reflect the fetched data.
	 *
	 * @param {Object} meta The meta values that were saved.
	 */
	function updateMetaboxUI( meta ) {
		// Content ID input.
		const contentIdInput = document.getElementById(
			'beyondwords_content_id'
		);
		if ( contentIdInput && meta.beyondwords_content_id !== undefined ) {
			contentIdInput.value = meta.beyondwords_content_id;
		}

		// Generate audio checkbox — fetched content sets this to '0'.
		const generateAudioCheckbox = document.getElementById(
			'beyondwords_generate_audio'
		);
		if ( generateAudioCheckbox ) {
			generateAudioCheckbox.checked =
				meta.beyondwords_generate_audio === '1';
		}

		// Language select — only update if the value exists as an option.
		// Match option values directly so a malformed API value can't throw.
		const languageSelect = document.getElementById(
			'beyondwords_language_code'
		);
		if ( languageSelect && meta.beyondwords_language_code ) {
			const hasOption = [ ...languageSelect.options ].some(
				( option ) => option.value === meta.beyondwords_language_code
			);
			if ( hasOption ) {
				languageSelect.value = meta.beyondwords_language_code;
			}
		}

		// Clear any previous error notices rendered by PHP.
		const errorContainer = document.getElementById(
			'beyondwords-metabox-errors'
		);
		if ( errorContainer ) {
			errorContainer.remove();
		}

		// Re-initialise the player preview with the fetched content. Route
		// through initMetaboxPlayer so a just-fetched-but-still-processing
		// content is polled until `processed` rather than embedded straight
		// away (which would request — and CDN-cache a 404 for — unready content).
		if ( meta.beyondwords_content_id && meta.beyondwords_project_id ) {
			let playerContainer = document.getElementById(
				'beyondwords-metabox-player'
			);

			if ( ! playerContainer ) {
				// Create the container if the post had no content before.
				const metabox = document.getElementById( 'beyondwords' );
				const inner = metabox && metabox.querySelector( '.inside' );
				if ( inner ) {
					playerContainer = document.createElement( 'div' );
					playerContainer.id = 'beyondwords-metabox-player';
					playerContainer.style.margin = '13px 0';
					inner.insertBefore( playerContainer, inner.firstChild );
				}
			}

			if ( playerContainer ) {
				playerContainer.setAttribute(
					'data-project-id',
					meta.beyondwords_project_id
				);
				playerContainer.setAttribute(
					'data-content-id',
					meta.beyondwords_content_id
				);
				playerContainer.setAttribute(
					'data-preview-token',
					meta.beyondwords_preview_token || ''
				);

				initMetaboxPlayer( playerContainer );
			}
		}
	}

	/**
	 * Set the loading state of the fetch button and input.
	 *
	 * @param {HTMLElement}  button  The fetch button.
	 * @param {HTMLElement}  input   The content ID input.
	 * @param {boolean}      loading Whether we are loading.
	 * @param {HTMLElement=} spinner The spinner element (removed when not loading).
	 * @return {HTMLElement|null} The spinner element when loading starts, null otherwise.
	 */
	function setLoading( button, input, loading, spinner ) {
		button.disabled = loading;
		input.readOnly = loading;

		if ( loading && ! spinner ) {
			const s = document.createElement( 'span' );
			s.className = 'spinner is-active';
			button.parentNode.insertBefore( s, button.nextSibling );
			return s;
		}

		if ( ! loading && spinner && spinner.parentNode ) {
			spinner.remove();
		}

		return null;
	}

	function handleFetchClick( event ) {
		const button = event.target.closest(
			'#beyondwords__content-id--fetch'
		);
		if ( ! button ) {
			return;
		}

		const input = document.getElementById( 'beyondwords_content_id' );
		const contentId = input ? input.value.trim() : '';
		const projectId = button.getAttribute( 'data-project-id' );
		const postIdInput = document.getElementById( 'post_ID' );
		const postId = postIdInput ? postIdInput.value : '';

		if ( ! contentId || ! projectId || ! postId ) {
			return;
		}

		if (
			typeof beyondwordsData === 'undefined' ||
			! beyondwordsData.root
		) {
			return;
		}

		const restBase = getRestBase( button );
		clearNotice();
		let spinner = setLoading( button, input, true );

		// Fetch content from the BeyondWords API.
		fetch(
			beyondwordsData.root +
				'beyondwords/v1/projects/' +
				encodeURIComponent( projectId ) +
				'/content/' +
				encodeURIComponent( contentId ),
			{
				method: 'GET',
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': beyondwordsData.nonce,
				},
			}
		)
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( response.statusText );
				}
				return response.json();
			} )
			.then( function ( data ) {
				const meta = {
					beyondwords_generate_audio: '0',
					beyondwords_project_id: String( data.project_id || '' ),
					beyondwords_content_id: data.id || '',
					beyondwords_preview_token: data.preview_token || '',
					beyondwords_language_code: data.language || '',
					beyondwords_body_voice_id: String(
						data.body_voice_id || ''
					),
					beyondwords_delete_content: '',
					beyondwords_error_message: '',
				};

				return savePostMeta( restBase, postId, meta ).then(
					function () {
						// Save succeeded. Refreshing the UI is best-effort —
						// contain failures so they can't reject the chain and
						// divert into the .catch, overwriting the saved meta.
						try {
							updateMetaboxUI( meta );
						} catch {
							// UI refresh failed; the saved content is intact.
						}
						showNotice(
							wp.i18n.__(
								'Content fetched and saved successfully.',
								'speechkit'
							),
							'success'
						);
					}
				);
			} )
			.catch( function ( fetchError ) {
				if ( fetchError.message === 'Failed to save' ) {
					showNotice(
						wp.i18n.__(
							'Failed to save fetched content.',
							'speechkit'
						),
						'error'
					);
					return;
				}

				// Persist the error message to post meta.
				const errorMeta = {
					beyondwords_content_id: contentId,
					beyondwords_error_message: wp.i18n.__(
						'Failed to fetch content. Please check the Content ID.',
						'speechkit'
					),
				};

				savePostMeta( restBase, postId, errorMeta )
					.catch( function () {
						// Ignore save failure — still show the notice.
					} )
					.then( function () {
						showNotice(
							wp.i18n.__(
								'Failed to fetch content. Please check the Content ID.',
								'speechkit'
							),
							'error'
						);
					} );
			} )
			.finally( function () {
				spinner = setLoading( button, input, false, spinner );
			} );
	}

	function init() {
		document.body.addEventListener( 'click', handleFetchClick );

		// Embed the player preview once its content has finished processing.
		const playerContainer = document.getElementById(
			'beyondwords-metabox-player'
		);
		if (
			playerContainer &&
			playerContainer.getAttribute( 'data-content-id' )
		) {
			initMetaboxPlayer( playerContainer );
		}
	}

	if ( document.readyState !== 'loading' ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}
} )();
