/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { Fragment, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PlayAudioCheck from './check';
import { useBeyondWordsPlayer } from './hooks';

function PlayAudio( {
	contentId,
	loadContentAs,
	previewToken,
	projectId,
	wrapper = Fragment,
} ) {
	const Wrapper = wrapper;

	const [ target, setTarget ] = useState( null );

	useBeyondWordsPlayer( {
		target,
		projectId,
		contentId,
		previewToken,
		loadContentAs,
	} );

	return (
		<PlayAudioCheck>
			<Wrapper>
				<div className="beyondwords-player-box-wrapper">
					<div ref={ setTarget }></div>
				</div>
			</Wrapper>
		</PlayAudioCheck>
	);
}

export default compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		// Project ID.
		const beyondwordsProjectId =
			getEditedPostAttribute( 'meta' ).beyondwords_project_id;
		const speechkitProjectId =
			getEditedPostAttribute( 'meta' ).speechkit_project_id;

		// Content ID.
		const beyondwordsContentId =
			getEditedPostAttribute( 'meta' ).beyondwords_content_id;
		const beyondwordsPodcastId =
			getEditedPostAttribute( 'meta' ).beyondwords_podcast_id;
		const speechkitPodcastId =
			getEditedPostAttribute( 'meta' ).speechkit_podcast_id;

		// Other attributes.
		const beyondwordsPlayerContent =
			getEditedPostAttribute( 'meta' ).beyondwords_player_content;
		const beyondwordsPreviewToken =
			getEditedPostAttribute( 'meta' ).beyondwords_preview_token;

		return {
			contentId:
				beyondwordsContentId ||
				beyondwordsPodcastId ||
				speechkitPodcastId,
			loadContentAs: beyondwordsPlayerContent
				? [ beyondwordsPlayerContent ]
				: [ 'article' ],
			previewToken: beyondwordsPreviewToken,
			projectId: beyondwordsProjectId || speechkitProjectId,
		};
	} ),
] )( PlayAudio );
