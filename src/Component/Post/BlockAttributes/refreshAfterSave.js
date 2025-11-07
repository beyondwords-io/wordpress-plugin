/**
 * WordPress dependencies
 */
import { subscribe, select, dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Refresh block attributes after save to get server-generated markers.
 *
 * When a post is saved, the server generates markers for blocks that don't have them.
 * This code ensures those markers appear in the editor without requiring a page refresh.
 *
 * @since 6.0.1
 */
let isSaving = false;
let isRefreshing = false;

subscribe( () => {
	const editor = select( 'core/editor' );

	if ( ! editor || isRefreshing ) {
		return;
	}

	const isAutosaving = editor.isAutosavingPost();

	// Skip autosaves
	if ( isAutosaving ) {
		return;
	}

	const currentlySaving = editor.isSavingPost();

	// Detect when save finishes
	if ( isSaving && ! currentlySaving && ! editor.isEditedPostDirty() ) {
		// Save just finished and post is clean (saved successfully)
		const postId = editor.getCurrentPostId();
		const postType = editor.getCurrentPostType();

		if ( postId && postType ) {
			// Refresh the post from the server to get updated markers
			refreshPostFromServer( postId, postType );
		}
	}

	isSaving = currentlySaving;
} );

/**
 * Refresh post data from server after save.
 *
 * @param {number} postId   The post ID.
 * @param {string} postType The post type.
 */
async function refreshPostFromServer( postId, postType ) {
	isRefreshing = true;

	try {
		// Get the REST base for this post type
		const postTypeObject = select( 'core' ).getPostType( postType );
		const restBase = postTypeObject?.rest_base || postType;

		// Fetch the updated post from the server
		const updatedPost = await apiFetch( {
			path: `/wp/v2/${ restBase }/${ postId }?context=edit`,
		} );

		if ( updatedPost && updatedPost.content && updatedPost.content.raw ) {
			const { updateBlockAttributes, resetBlocks } =
				dispatch( 'core/block-editor' );
			const blockEditor = select( 'core/block-editor' );

			// Parse the server blocks to get updated markers
			const serverBlocks = wp.blocks.parse( updatedPost.content.raw );
			const editorBlocks = blockEditor.getBlocks();

			// Update only the marker attributes
			const count = updateBlockMarkers(
				serverBlocks,
				editorBlocks,
				updateBlockAttributes
			);

			if ( count > 0 ) {
				// Force re-serialization by resetting blocks
				// This ensures updated markers are written to block comments
				const updatedBlocks = blockEditor.getBlocks();
				resetBlocks( updatedBlocks );

				// eslint-disable-next-line no-console
				console.log(
					`BeyondWords: Updated ${ count } block markers and re-serialized`
				);
			}
		}
	} catch ( error ) {
		// Log error for debugging
		console.error( 'BeyondWords: Failed to refresh block markers:', error );
	} finally {
		isRefreshing = false;
	}
}

/**
 * Recursively update block markers from server response.
 *
 * @param {Array}    serverBlocks          Blocks from server with updated markers.
 * @param {Array}    editorBlocks          Blocks currently in the editor.
 * @param {Function} updateBlockAttributes Function to update block attributes.
 *
 * @return {number} Count of blocks that were updated.
 */
function updateBlockMarkers(
	serverBlocks,
	editorBlocks,
	updateBlockAttributes
) {
	if (
		! serverBlocks ||
		! editorBlocks ||
		serverBlocks.length !== editorBlocks.length
	) {
		return 0;
	}

	let updatedCount = 0;

	for ( let i = 0; i < serverBlocks.length; i++ ) {
		const serverBlock = serverBlocks[ i ];
		const editorBlock = editorBlocks[ i ];

		// Skip if blocks don't match
		if (
			! serverBlock ||
			! editorBlock ||
			serverBlock.name !== editorBlock.name
		) {
			continue;
		}

		// Check if server block has a marker that editor block doesn't
		const serverMarker = serverBlock.attributes?.beyondwordsMarker;
		const editorMarker = editorBlock.attributes?.beyondwordsMarker;

		if ( serverMarker && serverMarker !== editorMarker ) {
			// Update the block attributes with server-generated marker
			updateBlockAttributes( editorBlock.clientId, {
				beyondwordsMarker: serverMarker,
			} );

			updatedCount++;
		}

		// Recursively process inner blocks
		if ( serverBlock.innerBlocks && editorBlock.innerBlocks ) {
			updatedCount += updateBlockMarkers(
				serverBlock.innerBlocks,
				editorBlock.innerBlocks,
				updateBlockAttributes
			);
		}
	}

	return updatedCount;
}
