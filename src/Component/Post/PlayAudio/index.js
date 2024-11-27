/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { compose, useDebounce } from '@wordpress/compose';
import { useDispatch, withSelect } from '@wordpress/data';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';

/**
 * External dependencies
 */
import ScriptTag from 'react-script-tag';

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

	const [ player, setPlayer] = useState(null);
	const [ playbackErroredListener, setPlaybackErroredListener ] = useState( null );

	const {
		createErrorNotice,
	} = useDispatch( noticesStore );

	useEffect(() => {
		return () => {
			if ( ! player ) {
				return;
			}
			if ( playbackErroredListener ) {
				player.removeEventListener('PlaybackErrored', playbackErroredListener);
			}
			player.destroy();
		}
	}, [] );

	function initPlayer() {
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
			target: this,
			widgetStyle: 'none',
		} );

		setPlaybackErroredListener(playerInstance.addEventListener('PlaybackErrored', () => {
			createErrorNotice( __( '🔊 There was an error playing the audio. Please try again.', 'speechkit' ), {
				id: 'beyondwords-player-notice',
				isDismissible: true,
			} );
		} ) );

		setPlayer( playerInstance );
	}

	// Debounce initPlayer to prevent multiple stacked player instances
	const debouncedInitPlayer = useDebounce(initPlayer, 500);

	// @todo allow player script src to be overridden for testing
	const umdSrc =
		'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js';

	return (
		<PlayAudioCheck>
			<Wrapper>
				<div>
					<div className="beyondwords-player-box-wrapper">
						<ScriptTag
							isHydrating={ false }
							async
							defer
							src={ umdSrc }
							onLoad={ debouncedInitPlayer }
						/>
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
