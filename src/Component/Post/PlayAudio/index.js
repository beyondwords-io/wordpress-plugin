/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { Fragment, useEffect, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PlayAudioCheck from './check';

function PlayAudio( {
	contentId,
	previewToken,
	projectId,
	wrapper = Fragment,
} ) {
	const Wrapper = wrapper;

	const targetRef = useRef( null );
	const [ player, setPlayer ] = useState( null );

	useEffect( () => {
		const script = document.createElement( 'script' );

		script.src =
			'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js';
		script.async = true;
		script.defer = true;
		script.onload = () => {
			if ( player || ! window.BeyondWords ) {
				return;
			}

			const playerInstance = new window.BeyondWords.Player( {
				adverts: [],
				analyticsConsent: 'none',
				contentId,
				introsOutros: [],
				playerStyle: 'small',
				previewToken,
				projectId,
				target: targetRef.current,
				widgetStyle: 'none',
			} );

			setPlayer( playerInstance );
		};

		document.body.appendChild( script );

		return () => {
			if ( player ) {
				player.destroy();
			}

			document.body.removeChild( script );
		};
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	return (
		<PlayAudioCheck>
			<Wrapper>
				<div>
					<div className="beyondwords-player-box-wrapper">
						<div ref={ targetRef }></div>
					</div>
				</div>
			</Wrapper>
		</PlayAudioCheck>
	);
}

export default compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		const beyondwordsPreviewToken =
			getEditedPostAttribute( 'meta' ).beyondwords_preview_token;
		const beyondwordsProjectId =
			getEditedPostAttribute( 'meta' ).beyondwords_project_id;
		const speechkitProjectId =
			getEditedPostAttribute( 'meta' ).speechkit_project_id;

		const beyondwordsContentId =
			getEditedPostAttribute( 'meta' ).beyondwords_content_id;
		const beyondwordsPodcastId =
			getEditedPostAttribute( 'meta' ).beyondwords_podcast_id;
		const speechkitPodcastId =
			getEditedPostAttribute( 'meta' ).speechkit_podcast_id;

		return {
			contentId:
				beyondwordsContentId ||
				beyondwordsPodcastId ||
				speechkitPodcastId,
			previewToken: beyondwordsPreviewToken,
			projectId: beyondwordsProjectId || speechkitProjectId,
		};
	} ),
] )( PlayAudio );
