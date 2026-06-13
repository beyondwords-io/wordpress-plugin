/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

/**
 * Whether the post has everything the BeyondWords player needs to load a
 * preview.
 *
 * Shared by the sidebar Preview panel (via the `withSelect` below) and the
 * `beyondwords/player` block, so both agree on when to show a live player
 * versus a placeholder. Legacy `podcast_id` keys are recognised so posts
 * upgraded from older plugin versions still preview correctly.
 *
 * @param {Function} select Redux-style select() from `@wordpress/data`.
 *
 * @return {boolean} True when the player can load.
 */
export function selectHasPlayAudioAction( select ) {
	const { getEditedPostAttribute } = select( 'core/editor' );

	const status = getEditedPostAttribute( 'status' );
	const projectId = getEditedPostAttribute( 'meta' ).beyondwords_project_id;
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

	return Boolean(
		status !== 'pending' && ( hasClientSideContent || hasRestApiContent )
	);
}

export function PlayAudioCheck( { hasPlayAudioAction, children } ) {
	if ( ! hasPlayAudioAction ) {
		return null;
	}

	return children;
}

export default compose( [
	withSelect( ( select ) => ( {
		hasPlayAudioAction: selectHasPlayAudioAction( select ),
	} ) ),
] )( PlayAudioCheck );
