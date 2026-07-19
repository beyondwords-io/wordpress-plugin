/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PlayAudioCheck from './check';
import { useBeyondWordsPlayer } from './hooks';

/**
 * The message to show when the player did not (yet) embed.
 *
 * @param {string|undefined} status   Last-seen content status.
 * @param {boolean}          timedOut Whether polling gave up.
 *
 * @return {string|null} The message, or null when there is nothing to say.
 */
function terminalMessage( status, timedOut ) {
	if ( timedOut ) {
		return __(
			'Generation is taking longer than expected. Refresh to check again.',
			'speechkit'
		);
	}
	if ( status === 'error' ) {
		return __( 'Generation failed.', 'speechkit' );
	}
	if ( status === 'skipped' ) {
		return __( 'No content was generated.', 'speechkit' );
	}
	return null;
}

function PlayAudio( {
	contentId,
	previewToken,
	projectId,
	sourceId,
	wrapper = Fragment,
} ) {
	const Wrapper = wrapper;

	const [ target, setTarget ] = useState( null );

	const { player, status, isPolling, timedOut } = useBeyondWordsPlayer( {
		target,
		projectId,
		sourceId,
		contentId,
		previewToken,
	} );

	const message =
		! isPolling && ! player ? terminalMessage( status, timedOut ) : null;

	return (
		<PlayAudioCheck>
			<Wrapper>
				<div className="beyondwords-player-box-wrapper">
					{ /* Scoped to the status text so the player's own DOM
					     isn't announced when it embeds. */ }
					{ ( isPolling || message ) && (
						<div role="status" aria-live="polite">
							{ isPolling && (
								<p className="beyondwords-player-loading">
									<Spinner />
									{ __( 'Generating…', 'speechkit' ) }
								</p>
							) }
							{ message && (
								<p className="beyondwords-player-message">
									{ message }
								</p>
							) }
						</div>
					) }
					{ /* Always mounted so the player has a stable target. */ }
					<div ref={ setTarget }></div>
				</div>
			</Wrapper>
		</PlayAudioCheck>
	);
}

export default compose( [
	withSelect( ( select ) => {
		const { getCurrentPostId, getEditedPostAttribute } =
			select( 'core/editor' );

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
		const beyondwordsPreviewToken =
			getEditedPostAttribute( 'meta' ).beyondwords_preview_token;

		return {
			contentId:
				beyondwordsContentId ||
				beyondwordsPodcastId ||
				speechkitPodcastId,
			previewToken: beyondwordsPreviewToken,
			projectId: beyondwordsProjectId || speechkitProjectId,
			sourceId: getCurrentPostId(),
		};
	} ),
] )( PlayAudio );
