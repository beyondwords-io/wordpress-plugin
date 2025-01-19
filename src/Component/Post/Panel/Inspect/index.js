/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	PanelBody,
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import { compose, useCopyToClipboard } from '@wordpress/compose';
import { useDispatch, withDispatch, withSelect } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';

export function PostInspectPanel( {
	// Current custom fields
	beyondwordsDeleteContent,
	beyondwordsDisabled,
	beyondwordsGenerateAudio,
	beyondwordsContentId,
	beyondwordsPreviewToken,
	beyondwordsPlayerContent,
	beyondwordsPlayerStyle,
	beyondwordsLanguageId,
	beyondwordsBodyVoiceId,
	beyondwordsTitleVoiceId,
	beyondwordsSummaryVoiceId,
	beyondwordsProjectId,
	beyondwordsErrorMessage,
	// Deprecated custom fields
	beyondwordsPodcastId,
	publishPostToSpeechkit,
	speechkitAccessKey,
	speechkitGenerateAudio,
	speechkitPodcastId,
	speechkitProjectId,
	speechkitDisabled,
	speechkitError,
	speechkitErrorMessage,
	speechkitInfo,
	speechkitResponse,
	speechkitLink,
	speechkitText,
	speechkitRetries,
	speechkitStatus,
	// System
	pluginVersion,
	wpVersion,
	wpPostId,
	// Other
	createWarningNotice,
	removeWarningNotice,
	setDeleteContent,
	didPostSaveRequestSucceed,
	isSavingPost,
	isAutosavingPost,
} ) {
	const [ removed, setRemoved ] = useState( false );
	const { createNotice } = useDispatch( noticesStore );

	useEffect( () => {
		if ( isSavingPost && ! isAutosavingPost && didPostSaveRequestSucceed ) {
			removeWarningNotice();
		}
	}, [ didPostSaveRequestSucceed, isAutosavingPost, isSavingPost ] );

	useEffect( () => {
		if ( isSavingPost && ! isAutosavingPost && didPostSaveRequestSucceed && removed ) {
			setRemoved( false );
		}
	}, [ didPostSaveRequestSucceed, isAutosavingPost, isSavingPost, removed ] );

	const memoizedMeta = useMemo(
		() => ( {
			plugin_version: pluginVersion,
			wp_version: wpVersion,
			beyondwords_generate_audio: beyondwordsGenerateAudio,
			beyondwords_project_id: beyondwordsProjectId,
			beyondwords_content_id: beyondwordsContentId,
			beyondwords_preview_token: beyondwordsPreviewToken,
			beyondwords_player_content: beyondwordsPlayerContent,
			beyondwords_player_style: beyondwordsPlayerStyle,
			beyondwords_language_id: beyondwordsLanguageId,
			beyondwords_body_voice_id: beyondwordsBodyVoiceId,
			beyondwords_title_voice_id: beyondwordsTitleVoiceId,
			beyondwords_summary_voice_id: beyondwordsSummaryVoiceId,
			beyondwords_error_message: beyondwordsErrorMessage,
			beyondwords_disabled: beyondwordsDisabled,
			beyondwords_delete_content: beyondwordsDeleteContent,
			// Deprecated
			beyondwords_podcast_id: beyondwordsPodcastId,
			publish_post_to_speechkit: publishPostToSpeechkit,
			speechkit_generate_audio: speechkitGenerateAudio,
			speechkit_project_id: speechkitProjectId,
			speechkit_podcast_id: speechkitPodcastId,
			speechkit_error_message: speechkitErrorMessage,
			speechkit_disabled: speechkitDisabled,
			speechkit_access_key: speechkitAccessKey,
			speechkit_error: speechkitError,
			speechkit_info: speechkitInfo,
			speechkit_response: speechkitResponse,
			speechkit_retries: speechkitRetries,
			speechkit_status: speechkitStatus,
			_speechkit_link: speechkitLink,
			_speechkit_text: speechkitText,
		} ),
		[]
	);

	const getTextToCopy = () =>
		[
			'```',
			`beyondwords_generate_audio\r\n${ beyondwordsGenerateAudio }`,
			`beyondwords_project_id\r\n${ beyondwordsProjectId }`,
			`beyondwords_content_id\r\n${ beyondwordsContentId }`,
			`beyondwords_preview_token\r\n${ beyondwordsPreviewToken }`,
			`beyondwords_player_content\r\n${ beyondwordsPlayerContent }`,
			`beyondwords_player_style\r\n${ beyondwordsPlayerStyle }`,
			`beyondwords_language_id\r\n${ beyondwordsLanguageId }`,
			`beyondwords_body_voice_id\r\n${ beyondwordsBodyVoiceId }`,
			`beyondwords_title_voice_id\r\n${ beyondwordsTitleVoiceId }`,
			`beyondwords_summary_voice_id\r\n${ beyondwordsSummaryVoiceId }`,
			`beyondwords_error_message\r\n${ beyondwordsErrorMessage }`,
			`beyondwords_disabled\r\n${ beyondwordsDisabled }`,
			`beyondwords_delete_content\r\n${ beyondwordsDeleteContent }`,
			`=== ${ __( 'Deprecated', 'speechkit' ) } ===`,
			`beyondwords_podcast_id\r\n${ beyondwordsPodcastId }`,
			`publish_post_to_speechkit\r\n${ publishPostToSpeechkit }`,
			`speechkit_generate_audio\r\n${ speechkitGenerateAudio }`,
			`speechkit_project_id\r\n${ speechkitProjectId }`,
			`speechkit_podcast_id\r\n${ speechkitPodcastId }`,
			`speechkit_error_message\r\n${ speechkitErrorMessage }`,
			`speechkit_disabled\r\n${ speechkitDisabled }`,
			`speechkit_access_key\r\n${ speechkitAccessKey }`,
			`speechkit_error\r\n${ speechkitError }`,
			`speechkit_info\r\n${ speechkitInfo }`,
			`speechkit_response\r\n${ speechkitResponse }`,
			`speechkit_retries\r\n${ speechkitRetries }`,
			`speechkit_status\r\n${ speechkitStatus }`,
			`_speechkit_link\r\n${ speechkitLink }`,
			`_speechkit_text\r\n${ speechkitText }`,
			`=== ${ __( 'System', 'speechkit' ) } ===`,
			`plugin_version\r\n${ pluginVersion }`,
			`wp_version\r\n${ wpVersion }`,
			`wp_post_id\r\n${ wpPostId }`,
			`=== ${ __( 'Copied using the Block Editor', 'speechkit' ) } ===`,
			'```',
		].join( '\r\n\r\n' ) + '\r\n\r\n';

	const copyToClipboardRef = useCopyToClipboard( getTextToCopy(), () => {
		createNotice( 'info', __( 'Copied data to clipboard.', 'speechkit' ), {
			isDismissible: true,
			type: 'snackbar',
		} );
	} );

	const hasBeyondwordsData = Object.values( memoizedMeta ).some(
		( x ) => !! x?.length
	);

	const handleRemoveButtonClick = ( e ) => {
		e.stopPropagation();

		if ( removed ) {
			setRemoved( false );
			setDeleteContent( false );
			removeWarningNotice();
		} else {
			setRemoved( true );
			setDeleteContent( true );
			createWarningNotice();
		}
	};

	return (
		<PanelBody
			title={ __( 'Inspect', 'speechkit' ) }
			initialOpen={ false }
			className={ 'beyondwords beyondwords-sidebar__inspect' }
		>
			<TextControl
				label="beyondwords_generate_audio"
				readOnly
				value={ beyondwordsGenerateAudio }
			/>

			<TextControl
				label="beyondwords_project_id"
				readOnly
				value={ beyondwordsProjectId }
			/>

			<TextControl
				label="beyondwords_preview_token"
				readOnly
				value={ beyondwordsPreviewToken }
			/>

			<TextControl
				label="beyondwords_content_id"
				readOnly
				value={ beyondwordsContentId }
			/>

			<TextControl
				label="beyondwords_player_content"
				readOnly
				value={ beyondwordsPlayerContent }
			/>

			<TextControl
				label="beyondwords_player_style"
				readOnly
				value={ beyondwordsPlayerStyle }
			/>

			<TextControl
				label="beyondwords_language_id"
				readOnly
				value={ beyondwordsLanguageId }
			/>

			<TextControl
				label="beyondwords_body_voice_id"
				readOnly
				value={ beyondwordsBodyVoiceId }
			/>

			<TextControl
				label="beyondwords_title_voice_id"
				readOnly
				value={ beyondwordsTitleVoiceId }
			/>

			<TextControl
				label="beyondwords_summary_voice_id"
				readOnly
				value={ beyondwordsSummaryVoiceId }
			/>

			{ /* eslint-disable-next-line prettier/prettier */ }
			<TextareaControl
				label="beyondwords_error_message"
				readOnly
				rows="3"
				value={ beyondwordsErrorMessage }
			/>

			<TextControl
				label="beyondwords_disabled"
				readOnly
				value={ beyondwordsDisabled }
			/>

			<TextControl
				label="beyondwords_delete_content"
				readOnly
				value={ beyondwordsDeleteContent }
			/>

			<hr />

			<Button
				id="beyondwords-inspect-copy"
				variant="secondary"
				ref={ copyToClipboardRef }
				disabled={ removed }
			>
				{ __( 'Copy', 'speechkit' ) }
			</Button>

			<Button
				isDestructive
				style={ { float: 'right' } }
				id="beyondwords-inspect-remove"
				onClick={ handleRemoveButtonClick }
				disabled={ ! hasBeyondwordsData }
			>
				{ removed
					? __( 'Restore', 'speechkit' )
					: __( 'Remove', 'speechkit' ) }
			</Button>
		</PanelBody>
	);
}

export default compose( [
	withSelect( ( select ) => {
		const {
			didPostSaveRequestSucceed,
			getCurrentPostId,
			getCurrentPostType,
			getEditedPostAttribute,
			isSavingPost,
			isAutosavingPost,
		} = select( 'core/editor' );

		const { getSettings } = select( 'beyondwords/settings' );

		const { pluginVersion, wpVersion } = getSettings();

		return {
			// Current custom fields
			beyondwordsDeleteContent:
				getEditedPostAttribute( 'meta' ).beyondwords_delete_content,
			beyondwordsDisabled:
				getEditedPostAttribute( 'meta' ).beyondwords_disabled,
			beyondwordsGenerateAudio:
				getEditedPostAttribute( 'meta' ).beyondwords_generate_audio,
			beyondwordsContentId:
				getEditedPostAttribute( 'meta' ).beyondwords_content_id,
			beyondwordsPreviewToken:
				getEditedPostAttribute( 'meta' ).beyondwords_preview_token,
			beyondwordsPlayerContent:
				getEditedPostAttribute( 'meta' ).beyondwords_player_content,
			beyondwordsPlayerStyle:
				getEditedPostAttribute( 'meta' ).beyondwords_player_style,
			beyondwordsLanguageId:
				getEditedPostAttribute( 'meta' ).beyondwords_language_id,
			beyondwordsBodyVoiceId:
				getEditedPostAttribute( 'meta' ).beyondwords_body_voice_id,
			beyondwordsTitleVoiceId:
				getEditedPostAttribute( 'meta' ).beyondwords_title_voice_id,
			beyondwordsSummaryVoiceId:
				getEditedPostAttribute( 'meta' ).beyondwords_summary_voice_id,
			beyondwordsProjectId:
				getEditedPostAttribute( 'meta' ).beyondwords_project_id,
			beyondwordsErrorMessage:
				getEditedPostAttribute( 'meta' ).beyondwords_error_message,
			// Deprecated custom fields
			beyondwordsPodcastId:
				getEditedPostAttribute( 'meta' ).beyondwords_podcast_id,
			publishPostToSpeechkit:
				getEditedPostAttribute( 'meta' ).publish_post_to_speechkit,
			speechkitAccessKey:
				getEditedPostAttribute( 'meta' ).speechkit_access_key,
			speechkitGenerateAudio:
				getEditedPostAttribute( 'meta' ).speechkit_generate_audio,
			speechkitPodcastId:
				getEditedPostAttribute( 'meta' ).speechkit_podcast_id,
			speechkitProjectId:
				getEditedPostAttribute( 'meta' ).speechkit_project_id,
			speechkitDisabled:
				getEditedPostAttribute( 'meta' ).speechkit_disabled,
			speechkitError: getEditedPostAttribute( 'meta' ).speechkit_error,
			speechkitErrorMessage:
				getEditedPostAttribute( 'meta' ).speechkit_error_message,
			speechkitInfo: getEditedPostAttribute( 'meta' ).speechkit_info,
			speechkitResponse:
				getEditedPostAttribute( 'meta' ).speechkit_response,
			speechkitLink: getEditedPostAttribute( 'meta' )._speechkit_link,
			speechkitText: getEditedPostAttribute( 'meta' )._speechkit_text,
			speechkitRetries:
				getEditedPostAttribute( 'meta' ).speechkit_retries,
			speechkitStatus: getEditedPostAttribute( 'meta' ).speechkit_status,
			// System
			pluginVersion,
			wpVersion,
			wpPostId: getCurrentPostId(),
			// Other
			currentPostType: getCurrentPostType(),
			didPostSaveRequestSucceed: didPostSaveRequestSucceed(),
			isSavingPost: isSavingPost(),
			isAutosavingPost: isAutosavingPost(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { editPost } = dispatch( 'core/editor' );
		const { createNotice, removeNotice } = dispatch( 'core/notices' );

		return {
			createWarningNotice: () =>
				createNotice(
					'warning',
					__(
						// eslint-disable-next-line max-len
						'The BeyondWords data for this post will be removed when the post is saved.',
						'speechkit'
					),
					{
						id: 'beyondwords-remove-post-data--warning',
						isDismissible: false,
						speak: true,
					}
				),
			removeWarningNotice: () =>
				removeNotice( 'beyondwords-remove-post-data--warning' ),
			setDeleteContent: ( deleteContent ) => {
				// Update the Post Meta (AKA the Custom Field)
				editPost( {
					meta: {
						/* eslint-disable-next-line camelcase */
						beyondwords_delete_content: deleteContent ? '1' : '',
					},
				} );
			},
		};
	} ),
] )( PostInspectPanel );
