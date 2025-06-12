/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Modal,
	TextControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { useSelect, dispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { updatePostMeta } from './utils';

const FetchModal = ( { onClose } ) => {
	const postId = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostId(),
		[]
	);

	const postProjectId = useSelect(
		( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' )
				?.beyondwords_project_id,
		[]
	);

	const settingsProjectId = useSelect(
		( select ) => select( 'beyondwords/settings' ).getSettings()?.projectId,
		[]
	);

	const restUrl = useSelect(
		( select ) => select( 'beyondwords/settings' ).getSettings()?.restUrl,
		[]
	);

	const [ contentId, setContentId ] = useState( '' );
	const [ projectId, setProjectId ] = useState(
		postProjectId || settingsProjectId || ''
	);
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	const handleSubmit = async () => {
		setIsLoading( true );
		setError( null );

		try {
			const response = await fetch(
				`${ restUrl }beyondwords/v1/projects/${ projectId }/content/${ contentId }`,
				{
					credentials: 'same-origin',
					headers: {
						'X-WP-Nonce': window.wpApiSettings?.nonce,
					},
				}
			);

			if ( ! response.ok ) {
				throw new Error(
					__(
						'Failed to fetch content. Please check the Project and Content IDs.',
						'speechkit'
					)
				);
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
				beyondwords_body_voice_id: body_voice_id || '',
				beyondwords_content_id: id || '',
				beyondwords_fetched_audio: '1',
				beyondwords_generate_audio: '1',
				beyondwords_language_code: language || '',
				beyondwords_preview_token: preview_token || '',
				beyondwords_project_id: project_id || '',
				beyondwords_title_voice_id: title_voice_id || '',
				beyondwords_summary_voice_id: summary_voice_id || '',
			};
			/* eslint-enable camelcase */

			// Update the post meta in the database.
			await updatePostMeta( postId, meta );

			// @todo editPost triggers the "Save" button in the editor, which we do not want.
			// Update the post meta in the editor state.
			// dispatch( 'core/editor' ).editPost( { meta } );

			// Show success notice.
			dispatch( 'core/notices' ).createNotice(
				'success',
				__(
					// eslint-disable-next-line max-len
					'🔊 Content fetched and saved successfully. Audio players and the Inspect Panel data will refresh after this page is reloaded.',
					'speechkit'
				),
				{
					isDismissible: true,
				}
			);

			setIsLoading( false );
			onClose();
		} catch ( err ) {
			setError( err.message );
			setIsLoading( false );
		}
	};

	return (
		<Modal
			title={ __( 'Fetch Content' ) }
			onRequestClose={ onClose }
			size="medium"
		>
			<TextControl
				label={ __( 'Project ID' ) }
				value={ projectId }
				disabled={ isLoading }
				onChange={ setProjectId }
				__next40pxDefaultSize
				// __nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Content ID' ) }
				placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
				value={ contentId }
				disabled={ isLoading }
				onChange={ setContentId }
				__next40pxDefaultSize
				// __nextHasNoMarginBottom
			/>
			<Button
				variant="primary"
				onClick={ handleSubmit }
				disabled={ isLoading || ! contentId }
				style={ { marginBottom: '8px' } }
			>
				{ isLoading ? (
					<>
						<Spinner style={ { marginRight: '6px' } } />
						{ __( 'Fetching…', 'speechkit' ) }
					</>
				) : (
					__( 'Submit', 'speechkit' )
				) }
			</Button>
			<Button
				isDestructive
				style={ { float: 'right' } }
				id="beyondwords-fetch-cancel"
				onClick={ onClose }
			>
				{ __( 'Cancel', 'speechkit' ) }
			</Button>
			{ error && (
				<div aria-live="assertive">
					<Notice
						status="error"
						isDismissible={ false }
						style={ { marginTop: '8px', marginBottom: '8px' } }
					>
						{ error }
					</Notice>
				</div>
			) }
		</Modal>
	);
};

const FetchButton = () => {
	const [ isModalOpen, setIsModalOpen ] = useState( false );

	return (
		<>
			<Button
				onClick={ () => setIsModalOpen( true ) }
				variant="secondary"
			>
				{ __( 'Fetch', 'speechkit' ) }
			</Button>
			{ isModalOpen && (
				<FetchModal onClose={ () => setIsModalOpen( false ) } />
			) }
		</>
	);
};

export default FetchButton;
