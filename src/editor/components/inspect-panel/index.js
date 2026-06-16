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
	// System
	pluginVersion,
	wpVersion,
	wpPostId,
	// Live post meta + the current/deprecated key lists (sourced from PHP via
	// the beyondwords/settings store) that drive the Copy and Remove controls.
	meta,
	currentMetaKeys,
	deprecatedMetaKeys,
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

	// `meta` is the live post meta (getEditedPostAttribute('meta')); the current
	// and deprecated key lists come from PHP via the settings store. Deriving both
	// controls from the same live source means Copy and Remove can never disagree,
	// and the Remove button tracks edits made after mount (e.g. audio generated).
	const dataKeys = [
		...( currentMetaKeys ?? [] ),
		...( deprecatedMetaKeys ?? [] ),
	];
	const hasData = hasBeyondwordsData( meta, dataKeys );

	// System diagnostics aren't post meta, so add them for the copied payload.
	const copyMeta = {
		...meta,
		plugin_version: pluginVersion,
		wp_version: wpVersion,
		wp_post_id: wpPostId,
	};

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
		getTextToCopy( copyMeta, currentMetaKeys, deprecatedMetaKeys ),
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

		const { pluginVersion, wpVersion, inspectMetaKeys } = getSettings();

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
			// Live post meta + the current/deprecated key lists (from PHP via the
			// settings store) that drive the Copy and Remove controls.
			meta: getEditedPostAttribute( 'meta' ),
			currentMetaKeys: inspectMetaKeys?.current,
			deprecatedMetaKeys: inspectMetaKeys?.deprecated,
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
