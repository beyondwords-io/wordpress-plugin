/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

export function PlayAudioCheck( { hasPlayAudioAction, children } ) {
	if ( ! hasPlayAudioAction ) {
		return null;
	}

	return children;
}

export default compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		const status = getEditedPostAttribute( 'status' );
		const projectId =
			getEditedPostAttribute( 'meta' ).beyondwords_project_id;
		const integrationMethod =
			getEditedPostAttribute( 'meta' ).beyondwords_integration_method;

		// Get Content ID, inc fallbacks for legacy field names.
		const beyondwordsContentId =
			getEditedPostAttribute( 'meta' ).beyondwords_content_id;
		const beyondwordsPodcastId =
			getEditedPostAttribute( 'meta' ).beyondwords_podcast_id;
		const speechkitPodcastId =
			getEditedPostAttribute( 'meta' ).speechkit_podcast_id;

		const contentId =
			beyondwordsContentId || beyondwordsPodcastId || speechkitPodcastId;

		const isClientSide = integrationMethod === 'client-side';

		const hasClientSideContent = isClientSide && projectId;

		const hasRestApiContent = ! isClientSide && projectId && contentId;

		return {
			hasPlayAudioAction:
				status !== 'pending' &&
				( hasClientSideContent || hasRestApiContent ),
		};
	} ),
] )( PlayAudioCheck );
