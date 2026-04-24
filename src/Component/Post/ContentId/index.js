/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Flex,
	FlexItem,
	TextControl,
	Spinner,
} from '@wordpress/components';
import { useSelect, useDispatch, select } from '@wordpress/data';
import { Fragment, useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Internal utilities
const updatePostMeta = ( postId, meta ) => {
	const postType = select( 'core/editor' ).getCurrentPostType();
	const postTypeInfo = select( 'core' ).getPostType( postType );
	const restBase = postTypeInfo?.rest_base || postType;

	return apiFetch( {
		path: `/wp/v2/${ restBase }/${ postId }`,
		method: 'POST',
		data: { meta },
	} );
};
export function ContentId( { wrapper } ) {
	const Wrapper = wrapper || Fragment;

	const { editPost } = useDispatch( 'core/editor' );

	const postId = useSelect(
		() => select( 'core/editor' ).getCurrentPostId(),
		[]
	);

	const savedContentId = useSelect(
		() =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' )
				?.beyondwords_content_id || '',
		[]
	);

	const settingsProjectId = useSelect( () => {
		const metaProjectId =
			select( 'core/editor' ).getEditedPostAttribute(
				'meta'
			)?.beyondwords_project_id;
		const settings = select( 'beyondwords/settings' ).getSettings() || {};

		return metaProjectId || settings.projectId;
	}, [] );

	const restUrl = useSelect(
		() => select( 'beyondwords/settings' ).getSettings()?.restUrl,
		[]
	);

	const [ contentId, setContentId ] = useState( savedContentId );
	const [ isLoading, setIsLoading ] = useState( false );

	// Keep local state in sync when savedContentId changes externally.
	useEffect( () => {
		setContentId( savedContentId );
	}, [ savedContentId ] );

	const handleFetch = async () => {
		if ( ! contentId || ! settingsProjectId ) {
			return;
		}

		setIsLoading( true );

		try {
			const response = await fetch(
				`${ restUrl }beyondwords/v1/projects/${ encodeURIComponent(
					settingsProjectId
				) }/content/${ encodeURIComponent( contentId ) }`,
				{
					credentials: 'same-origin',
					headers: {
						'X-WP-Nonce': window.wpApiSettings?.nonce,
					},
				}
			);

			if ( ! response.ok ) {
				const errorMeta = {
					beyondwords_content_id: contentId,
					beyondwords_error_message: __(
						'Failed to fetch content. Please check the Content ID.',
						'speechkit'
					),
				};

				await updatePostMeta( postId, errorMeta );
				editPost( { meta: errorMeta } );
				return;
			}

			const data = await response.json();

			/* eslint-disable camelcase */
			const {
				body_voice_id,
				id,
				language,
				preview_token,
				title_voice_id,
				summary_voice_id,
				project_id,
			} = data;

			const meta = {
				beyondwords_generate_audio: '0',
				beyondwords_project_id: String( project_id || '' ),
				beyondwords_content_id: id || '',
				beyondwords_preview_token: preview_token || '',
				beyondwords_language_code: language || '',
				beyondwords_title_voice_id: String( title_voice_id || '' ),
				beyondwords_summary_voice_id: String( summary_voice_id || '' ),
				beyondwords_body_voice_id: String( body_voice_id || '' ),
				beyondwords_delete_content: '',
				beyondwords_disabled: '',
				beyondwords_error_message: '',
			};
			/* eslint-enable camelcase */

			await updatePostMeta( postId, meta );
			editPost( { meta } );

			// Update local state with the returned content ID.
			setContentId( id || '' );
		} catch {
			const errorMeta = {
				beyondwords_content_id: contentId,
				beyondwords_error_message: __(
					'Failed to fetch content. Please check the Content ID.',
					'speechkit'
				),
			};

			try {
				await updatePostMeta( postId, errorMeta );
			} catch {
				// Persist failed — still update local editor state below.
			}
			editPost( { meta: errorMeta } );
		} finally {
			setIsLoading( false );
		}
	};

	return (
		<Wrapper>
			<Flex align="flex-end" gap={ 2 }>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'Content ID', 'speechkit' ) }
						value={ contentId }
						onChange={ setContentId }
						disabled={ isLoading }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
				<FlexItem>
					<Button
						variant="secondary"
						onClick={ handleFetch }
						disabled={ isLoading || ! contentId }
						__next40pxDefaultSize
					>
						{ isLoading ? (
							<Spinner style={ { margin: 0 } } />
						) : (
							__( 'Fetch', 'speechkit' )
						) }
					</Button>
				</FlexItem>
			</Flex>
		</Wrapper>
	);
}

export default ContentId;
