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
	const [ contentId, setContentId ] = useState( '' );
	const [ projectId, setProjectId ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	const { editPost } = useDispatch( editorStore );

	const postId = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostId(),
		[]
	);

	const handleSubmit = () => {
		setIsLoading( true );
		setError( null );

		fetch(
			`/wp-json/beyondwords/v1/projects/${ projectId }/content/${ contentId }`
		)
			.then( ( response ) => {
				console.log( 'Response:', response );
				if ( ! response.ok ) {
					throw new Error( 'Failed to fetch content' );
				}
				return response.json();
			} )
			.then( ( data ) => {
				editPost( { meta: { beyondwords_content_id: data.id } } );
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
			/>
			<TextControl
				label={ __( 'Content ID' ) }
				value={ contentId }
				disabled={ isLoading }
				onChange={ setContentId }
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
			<Button onClick={ () => setIsModalOpen( true ) } variant="primary">
				{ __( 'Fetch' ) }
			</Button>
			{ isModalOpen && (
				<FetchModal onClose={ () => setIsModalOpen( false ) } />
			) }
		</>
	);
};

export default FetchButton;
