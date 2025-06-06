import { useState } from '@wordpress/element';
import {
	Button,
	Modal,
	TextControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

const FetchModal = ( { onClose } ) => {
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

	const { editPost } = useDispatch( editorStore );

	const handleSubmit = () => {
		setIsLoading( true );
		setError( null );

		fetch(
			`${ restUrl }beyondwords/v1/projects/${ projectId }/content/${ contentId }`,
			{
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': window.wpApiSettings?.nonce,
				},
			}
		)
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( 'Failed to fetch content' );
				}
				return response.json();
			} )
			.then( ( data ) => {
				/* eslint-disable camelcase */
				const {
					body_voice_id,
					id,
					language,
					preview_token,
					title_voice_id,
					summary_voice_id,
				} = data;

				editPost( {
					meta: {
						beyondwords_body_voice_id: body_voice_id || '',
						beyondwords_content_id: id || '',
						beyondwords_generate_audio: '1',
						beyondwords_language_code: language || '',
						beyondwords_preview_token: preview_token || '',
						beyondwords_project_id: data.project_id || '',
						beyondwords_title_voice_id: title_voice_id || '',
						beyondwords_summary_voice_id: summary_voice_id || '',
					},
				} );
				/* eslint-enable camelcase */

				onClose();
			} )
			.catch( ( err ) => {
				setError( err.message );
				setIsLoading( false );
			} );
	};

	return (
		<Modal title={ __( 'Fetch Content' ) } onRequestClose={ onClose }>
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
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			<Button
				variant="primary"
				onClick={ handleSubmit }
				disabled={ isLoading || ! contentId }
			>
				{ isLoading ? <Spinner /> : __( 'OK' ) }
			</Button>
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
				{ __( 'Fetch' ) }
			</Button>
			{ isModalOpen && (
				<FetchModal onClose={ () => setIsModalOpen( false ) } />
			) }
		</>
	);
};

export default FetchButton;
