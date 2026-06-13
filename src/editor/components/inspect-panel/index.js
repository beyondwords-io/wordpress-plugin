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
import { useEffect, useState } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import Stack from '../stack';
import { getTextToCopy, hasBeyondwordsData } from './helpers';

export function PostInspectPanel( {
	// Current custom fields
	beyondwordsDeleteContent,
	beyondwordsGenerateAudio,
	beyondwordsIntegrationMethod,
	beyondwordsContentId,
	beyondwordsPreviewToken,
	beyondwordsLanguageCode,
	beyondwordsLanguageId,
	beyondwordsBodyVoiceId,
	beyondwordsProjectId,
	beyondwordsErrorMessage,
	beyondwordsSource,
	beyondwordsOutput,
	beyondwordsScriptTemplateId,
	beyondwordsVideoTemplateId,
	beyondwordsVideoSize,
	beyondwordsEmbed,
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
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ didPostSaveRequestSucceed, isAutosavingPost, isSavingPost ] );

	useEffect( () => {
		if (
			isSavingPost &&
			! isAutosavingPost &&
			didPostSaveRequestSucceed &&
			removed
		) {
			setRemoved( false );
		}
	}, [ didPostSaveRequestSucceed, isAutosavingPost, isSavingPost, removed ] );

	// Single live source of truth for the panel: the post's current custom-field
	// values keyed by field name. Rebuilt every render from the withSelect props
	// (getEditedPostAttribute('meta')) so both the Copy and Remove controls track
	// edits made after mount — e.g. audio being generated — instead of a snapshot.
	const meta = {
		// Current
		beyondwords_generate_audio: beyondwordsGenerateAudio,
		beyondwords_project_id: beyondwordsProjectId,
		beyondwords_content_id: beyondwordsContentId,
		beyondwords_integration_method: beyondwordsIntegrationMethod,
		beyondwords_preview_token: beyondwordsPreviewToken,
		beyondwords_language_code: beyondwordsLanguageCode,
		beyondwords_language_id: beyondwordsLanguageId,
		beyondwords_body_voice_id: beyondwordsBodyVoiceId,
		beyondwords_error_message: beyondwordsErrorMessage,
		beyondwords_delete_content: beyondwordsDeleteContent,
		beyondwords_source: beyondwordsSource,
		beyondwords_output: beyondwordsOutput,
		beyondwords_script_template_id: beyondwordsScriptTemplateId,
		beyondwords_video_template_id: beyondwordsVideoTemplateId,
		beyondwords_video_size: beyondwordsVideoSize,
		beyondwords_embed: beyondwordsEmbed,
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
		// System (diagnostics only — copied, never counted as removable data)
		plugin_version: pluginVersion,
		wp_version: wpVersion,
		wp_post_id: wpPostId,
	};

	const hasData = hasBeyondwordsData( meta );

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

	const copyToClipboardRef = useCopyToClipboard(
		getTextToCopy( meta ),
		() => {
			createNotice(
				'info',
				__( 'Copied data to clipboard.', 'speechkit' ),
				{
					isDismissible: true,
					type: 'snackbar',
				}
			);
		}
	);

	return (
		<PanelBody
			title={ __( 'Inspect', 'speechkit' ) }
			initialOpen={ false }
			className={ 'beyondwords beyondwords-sidebar__inspect' }
		>
			<Stack>
				<TextControl
					label="beyondwords_generate_audio"
					readOnly
					value={ beyondwordsGenerateAudio }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_integration_method"
					readOnly
					value={ beyondwordsIntegrationMethod }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_project_id"
					readOnly
					value={ beyondwordsProjectId }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_preview_token"
					readOnly
					value={ beyondwordsPreviewToken }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_content_id"
					readOnly
					value={ beyondwordsContentId }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_language_code"
					readOnly
					value={ beyondwordsLanguageCode }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_language_id"
					readOnly
					value={ beyondwordsLanguageId }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_body_voice_id"
					readOnly
					value={ beyondwordsBodyVoiceId }
					__next40pxDefaultSize
				/>

				<TextareaControl
					label="beyondwords_error_message"
					readOnly
					rows="3"
					value={ beyondwordsErrorMessage }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_delete_content"
					readOnly
					value={ beyondwordsDeleteContent }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_source"
					readOnly
					value={ beyondwordsSource }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_output"
					readOnly
					value={ beyondwordsOutput }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_script_template_id"
					readOnly
					value={ beyondwordsScriptTemplateId }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_video_template_id"
					readOnly
					value={ beyondwordsVideoTemplateId }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_video_size"
					readOnly
					value={ beyondwordsVideoSize }
					__next40pxDefaultSize
				/>

				<TextControl
					label="beyondwords_embed"
					readOnly
					value={ beyondwordsEmbed }
					__next40pxDefaultSize
				/>
			</Stack>

			<hr />

			<Button
				id="beyondwords-inspect-copy"
				variant="primary"
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
				disabled={ ! hasData }
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
			beyondwordsGenerateAudio:
				getEditedPostAttribute( 'meta' ).beyondwords_generate_audio,
			beyondwordsIntegrationMethod:
				getEditedPostAttribute( 'meta' ).beyondwords_integration_method,
			beyondwordsContentId:
				getEditedPostAttribute( 'meta' ).beyondwords_content_id,
			beyondwordsPreviewToken:
				getEditedPostAttribute( 'meta' ).beyondwords_preview_token,
			beyondwordsLanguageCode:
				getEditedPostAttribute( 'meta' ).beyondwords_language_code,
			beyondwordsLanguageId:
				getEditedPostAttribute( 'meta' ).beyondwords_language_id,
			beyondwordsBodyVoiceId:
				getEditedPostAttribute( 'meta' ).beyondwords_body_voice_id,
			beyondwordsProjectId:
				getEditedPostAttribute( 'meta' ).beyondwords_project_id,
			beyondwordsErrorMessage:
				getEditedPostAttribute( 'meta' ).beyondwords_error_message,
			beyondwordsSource:
				getEditedPostAttribute( 'meta' ).beyondwords_source,
			beyondwordsOutput:
				getEditedPostAttribute( 'meta' ).beyondwords_output,
			beyondwordsScriptTemplateId:
				getEditedPostAttribute( 'meta' ).beyondwords_script_template_id,
			beyondwordsVideoTemplateId:
				getEditedPostAttribute( 'meta' ).beyondwords_video_template_id,
			beyondwordsVideoSize:
				getEditedPostAttribute( 'meta' ).beyondwords_video_size,
			beyondwordsEmbed:
				getEditedPostAttribute( 'meta' ).beyondwords_embed,
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
						beyondwords_delete_content: deleteContent ? '1' : '',
					},
				} );
			},
		};
	} ),
] )( PostInspectPanel );
