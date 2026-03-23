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
import { useSelect, useDispatch } from '@wordpress/data';
import { Fragment, useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { updatePostMeta } from '../Panel/Inspect/fetch/utils';

export function ContentId( { wrapper } ) {
	const Wrapper = wrapper || Fragment;

	const { editPost } = useDispatch( 'core/editor' );

	const postId = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostId(),
		[]
	);

	const savedContentId = useSelect(
		( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' )
				?.beyondwords_content_id || '',
		[]
	);

	const settingsProjectId = useSelect( ( select ) => {
		const metaProjectId =
			select( 'core/editor' ).getEditedPostAttribute(
				'meta'
			)?.beyondwords_project_id;
		const settings = select( 'beyondwords/settings' ).getSettings() || {};

		return metaProjectId || settings.projectId;
	}, [] );

	const restUrl = useSelect(
		( select ) => select( 'beyondwords/settings' ).getSettings()?.restUrl,
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
				`${ restUrl }beyondwords/v1/projects/${ settingsProjectId }/content/${ contentId }`,
				{
					credentials: 'same-origin',
					headers: {
						'X-WP-Nonce': window.wpApiSettings?.nonce,
					},
				}
			);

			if ( ! response.ok ) {
				/* eslint-disable camelcase */
				const errorMeta = {
					beyondwords_content_id: contentId,
					beyondwords_error_message: __(
						'Failed to fetch content. Please check the Content ID.',
						'speechkit'
					),
				};
				/* eslint-enable camelcase */

				await updatePostMeta( postId, errorMeta );
				editPost( { meta: errorMeta } );
				setIsLoading( false );
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

			setIsLoading( false );
		} catch ( err ) {
			/* eslint-disable camelcase */
			const errorMeta = {
				beyondwords_content_id: contentId,
				beyondwords_error_message: __(
					'Failed to fetch content. Please check the Content ID.',
					'speechkit'
				),
			};
			/* eslint-enable camelcase */

			await updatePostMeta( postId, errorMeta );
			editPost( { meta: errorMeta } );
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
