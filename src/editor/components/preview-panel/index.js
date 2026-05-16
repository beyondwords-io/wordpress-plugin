/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import DisplayPlayer from '../display-player';
import PlayAudio from '../play-audio';

/**
 * Whether the post has audio/video ready to preview.
 *
 * Matches PlayAudioCheck / DisplayPlayerCheck so the panel hides itself when
 * either of those would also have nothing to render — i.e. before the first
 * successful generation. Legacy `podcast_id` keys are recognised so existing
 * posts upgraded from older plugin versions still preview correctly.
 *
 * @param {Function} select Redux-style select() from `@wordpress/data`.
 *
 * @return {boolean} True when a content/podcast id is set on the post.
 */
function hasGeneratedContent( select ) {
	const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
	return Boolean(
		meta?.beyondwords_content_id ||
			meta?.beyondwords_podcast_id ||
			meta?.speechkit_podcast_id
	);
}

export function PreviewPanel() {
	const hasContent = useSelect( hasGeneratedContent, [] );

	if ( ! hasContent ) {
		return null;
	}

	return (
		<PanelBody
			title={ __( 'Preview', 'speechkit' ) }
			initialOpen={ true }
			className="beyondwords beyondwords-sidebar__preview"
		>
			<PlayAudio wrapper={ PanelRow } />
			<DisplayPlayer wrapper={ PanelRow } />
		</PanelBody>
	);
}

export default PreviewPanel;
