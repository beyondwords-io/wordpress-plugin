/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Update post meta via the REST API
 *
 * @param {number} postId      - The post ID to update.
 * @param {Object} metaUpdates - Key-value pairs of meta fields to update.
 *
 * @return {Promise<Object>} - The updated post object.
 */
export async function updatePostMeta( postId, metaUpdates ) {
	const updatedPost = await apiFetch( {
		path: `/wp/v2/posts/${ postId }`,
		method: 'POST',
		data: {
			meta: metaUpdates,
		},
	} );

	if ( ! updatedPost || ! updatedPost.id ) {
		throw new Error( 'Failed to update post meta.' );
	}

	return updatedPost;
}
