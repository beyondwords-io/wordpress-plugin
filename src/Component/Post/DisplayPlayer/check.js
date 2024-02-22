/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

export function DisplayPlayerCheck( { hasDisplayPlayerAction, children } ) {
	if ( ! hasDisplayPlayerAction ) {
		return null;
	}

	return children;
}

export default compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		const beyondwordsContentId =
			getEditedPostAttribute( 'meta' ).beyondwords_content_id;
		const beyondwordsPodcastId =
			getEditedPostAttribute( 'meta' ).beyondwords_podcast_id;
		const speechkitPodcastId =
			getEditedPostAttribute( 'meta' ).speechkit_podcast_id;

		return {
			hasDisplayPlayerAction:
				!! beyondwordsContentId ||
				!! beyondwordsPodcastId ||
				!! speechkitPodcastId,
		};
	} ),
] )( DisplayPlayerCheck );
