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

		$button.prop( 'disabled', true ).text(
			wp.i18n.__( 'Fetching…', 'speechkit' )
		);

		$.ajax( {
			url:
				beyondwordsData.root +
				'beyondwords/v1/projects/' +
				projectId +
				'/content/' +
				contentId,
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', beyondwordsData.nonce );
			},
		} )
			.done( function ( data ) {
				// Save the fetched meta to the post via WP REST API.
				$.ajax( {
					url: beyondwordsData.root + 'wp/v2/posts/' + postId,
					method: 'POST',
					contentType: 'application/json',
					beforeSend: function ( xhr ) {
						xhr.setRequestHeader(
							'X-WP-Nonce',
							beyondwordsData.nonce
						);
					},
					data: JSON.stringify( {
						meta: {
							beyondwords_generate_audio: '0',
							beyondwords_project_id:
								String( data.project_id || '' ),
							beyondwords_content_id: data.id || '',
							beyondwords_preview_token:
								data.preview_token || '',
							beyondwords_language_code: data.language || '',
							beyondwords_title_voice_id:
								String( data.title_voice_id || '' ),
							beyondwords_summary_voice_id:
								String( data.summary_voice_id || '' ),
							beyondwords_body_voice_id:
								String( data.body_voice_id || '' ),
							beyondwords_delete_content: '',
							beyondwords_disabled: '',
							beyondwords_error_message: '',
						},
					} ),
				} )
					.done( function () {
						window.location.reload();
					} )
					.fail( function () {
						$button.prop( 'disabled', false ).text(
							wp.i18n.__( 'Fetch', 'speechkit' )
						);
						/* eslint-disable-next-line no-alert */
						window.alert(
							wp.i18n.__(
								'Failed to save fetched content.',
								'speechkit'
							)
						);
					} );
			} )
			.fail( function () {
				// Save error message to post meta.
				$.ajax( {
					url: beyondwordsData.root + 'wp/v2/posts/' + postId,
					method: 'POST',
					contentType: 'application/json',
					beforeSend: function ( xhr ) {
						xhr.setRequestHeader(
							'X-WP-Nonce',
							beyondwordsData.nonce
						);
					},
					data: JSON.stringify( {
						meta: {
							beyondwords_content_id: contentId,
							beyondwords_error_message: wp.i18n.__(
								'Failed to fetch content. Please check the Content ID.',
								'speechkit'
							),
						},
					} ),
				} ).always( function () {
					window.location.reload();
				} );
			} );
	} );
} );
