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

		const beyondwordsContentId =
			getEditedPostAttribute( 'meta' ).beyondwords_content_id;
		const beyondwordsPodcastId =
			getEditedPostAttribute( 'meta' ).beyondwords_podcast_id;
		const speechkitPodcastId =
			getEditedPostAttribute( 'meta' ).speechkit_podcast_id;
		const status = getEditedPostAttribute( 'status' );

		return {
			hasPlayAudioAction:
				status !== 'pending' &&
				( !! beyondwordsContentId ||
					!! beyondwordsPodcastId ||
					!! speechkitPodcastId ),
		};
	} ),
] )( PlayAudioCheck );
