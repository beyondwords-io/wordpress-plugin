/* global jQuery, beyondwordsData */

jQuery( document ).ready( function ( $ ) {
	$( 'body' ).on( 'click', '#beyondwords__content-id--fetch', function () {
		const $button = $( this );
		const $input = $( '#beyondwords_content_id' );
		const contentId = $input.val().trim();
		const projectId = $button.data( 'project-id' );
		const postId = $( '#post_ID' ).val();

		if ( ! contentId || ! projectId || ! postId ) {
			return;
		}

		if ( ! beyondwordsData || ! beyondwordsData.root ) {
			return;
		}

		$button.prop( 'disabled', true );
		$input.prop( 'readonly', true );

		// Show a WordPress spinner next to the button.
		const $spinner = $( '<span class="spinner is-active"></span>' );
		$button.after( $spinner );

		// Use native fetch to avoid jQuery ajaxSend hooks that append
		// form data to the request body, which corrupts our JSON payload.
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
				// Determine the correct REST base for the current post type.
				var postType = $( '#post_type' ).val() || 'post';
				var restBase;

				if ( postType === 'post' ) {
					restBase = 'posts';
				} else if ( postType === 'page' ) {
					restBase = 'pages';
				} else {
					// For custom post types, the REST base commonly matches the post type slug.
					restBase = postType;
				}

				// Save the fetched meta to the post via WP REST API.
				return fetch(
					beyondwordsData.root + 'wp/v2/' + restBase + '/' + postId,
					{
						method: 'POST',
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': beyondwordsData.nonce,
						},
						body: JSON.stringify( {
							meta: {
								beyondwords_generate_audio: '0',
								beyondwords_project_id: String(
									data.project_id || ''
								),
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
							},
						} ),
					}
				).then( function ( saveResponse ) {
					if ( ! saveResponse.ok ) {
						throw new Error( 'Failed to save' );
					}
					window.location.reload();
				} );
			} )
			.catch( function ( fetchError ) {
				// Determine if this was a fetch error or a save error.
				const isSaveError = fetchError.message === 'Failed to save';

				if ( isSaveError ) {
					$button.prop( 'disabled', false );
					$input.prop( 'readonly', false );
					$spinner.remove();
					/* eslint-disable-next-line no-alert */
					window.alert(
						wp.i18n.__(
							'Failed to save fetched content.',
							'speechkit'
						)
					);
					return;
				}

				// Save error message to post meta.
				fetch( beyondwordsData.root + 'wp/v2/posts/' + postId, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': beyondwordsData.nonce,
					},
					body: JSON.stringify( {
						meta: {
							beyondwords_content_id: contentId,
							beyondwords_error_message: wp.i18n.__(
								'Failed to fetch content. Please check the Content ID.',
								'speechkit'
							),
						},
					} ),
				} ).finally( function () {
					window.location.reload();
				} );
			} );
	} );
} );
