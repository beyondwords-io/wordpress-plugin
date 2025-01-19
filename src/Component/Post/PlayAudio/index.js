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
	const [ scriptAppended, setScriptAppended ] = useState( false );

	const script = document.createElement( 'script' );
	script.src =
		'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js';
	script.async = true;
	script.onload = function () {
		new window.BeyondWords.Player( {
			adverts: [],
			analyticsConsent: 'none',
			contentId,
			introsOutros: [],
			playerStyle: 'small',
			previewToken: previewToken || '',
			projectId,
			target: targetRef.current,
			widgetStyle: 'none',
		} );
	};

	useEffect( () => {
		if ( contentId && projectId && ! scriptAppended ) {
			document.body.appendChild( script );
			setScriptAppended( true );
		}

		return () => {
			if ( document.body.contains( script ) ) {
				document.body.removeChild( script );
			}
		};
	}, [ contentId, projectId, scriptAppended, script ] );

	return (
		<PlayAudioCheck>
			<Wrapper>
				<div className="beyondwords-player-box-wrapper">
					<div ref={ targetRef }></div>
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
