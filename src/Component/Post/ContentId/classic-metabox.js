/* global beyondwordsData */

( function () {
	'use strict';

	/**
	 * Show a dismissible notice inside the metabox.
	 *
	 * @param {string} message Notice text.
	 * @param {string} type    'success' or 'error'.
	 */
	function showNotice( message, type ) {
		const container = document.getElementById(
			'beyondwords-metabox-content-id'
		);
		if ( ! container ) {
			return;
		}

		// Remove any existing notice.
		const existing = container.querySelector(
			'.beyondwords-content-id-notice'
		);
		if ( existing ) {
			existing.remove();
		}

		const notice = document.createElement( 'div' );
		notice.className = 'beyondwords-content-id-notice';
		notice.style.cssText =
			'padding:8px 12px;margin:8px 0 0;border-left:4px solid ' +
			( type === 'error' ? '#d63638' : '#00a32a' ) +
			';background:#fff;';
		notice.textContent = message;

		container.appendChild( notice );

		// Auto-dismiss success notices after 6 seconds.
		if ( type === 'success' ) {
			setTimeout( function () {
				if ( notice.parentNode ) {
					notice.remove();
				}
			}, 6000 );
		}
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

		// Language select.
		const languageSelect = document.getElementById(
			'beyondwords_language_code'
		);
		if ( languageSelect && meta.beyondwords_language_code !== undefined ) {
			languageSelect.value = meta.beyondwords_language_code;
		}

		// Clear any previous error notices rendered by PHP.
		const errorContainer = document.getElementById(
			'beyondwords-metabox-errors'
		);
		if ( errorContainer ) {
			errorContainer.remove();
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

	document.addEventListener( 'DOMContentLoaded', function () {
		document.body.addEventListener( 'click', function ( event ) {
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
			let spinner = setLoading( button, input, true );

			// Fetch content from the BeyondWords API.
			fetch(
				beyondwordsData.root +
					'beyondwords/v1/projects/' +
					projectId +
					'/content/' +
					contentId,
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
						beyondwords_title_voice_id: String(
							data.title_voice_id || ''
						),
						beyondwords_summary_voice_id: String(
							data.summary_voice_id || ''
						),
						beyondwords_body_voice_id: String(
							data.body_voice_id || ''
						),
						beyondwords_delete_content: '',
						beyondwords_disabled: '',
						beyondwords_error_message: '',
					};

					return savePostMeta( restBase, postId, meta ).then(
						function () {
							updateMetaboxUI( meta );
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
		} );
	} );
} )();
