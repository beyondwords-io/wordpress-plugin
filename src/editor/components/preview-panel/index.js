/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ErrorNotice from '../error-notice';
import PlayAudio from '../play-audio';

/**
 * Whether the post has audio/video ready to preview.
 *
 * Matches PlayAudioCheck so the panel hides itself when PlayAudio would also
 * have nothing to render — i.e. before the first successful generation. Legacy
 * `podcast_id` keys are recognised so existing posts upgraded from older plugin
 * versions still preview correctly.
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

/**
 * Whether the post has a BeyondWords error message to surface.
 *
 * @param {Function} select Redux-style select() from `@wordpress/data`.
 *
 * @return {boolean} True when an error message is set on the post.
 */
function hasError( select ) {
	const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
	return Boolean(
		meta?.beyondwords_error_message || meta?.speechkit_error_message
	);
}

export function PreviewPanel() {
	// Show the panel when there's something to preview *or* an error to surface,
	// mirroring the error display in the document-settings panel.
	const showPanel = useSelect(
		( select ) => hasGeneratedContent( select ) || hasError( select ),
		[]
	);

	if ( ! showPanel ) {
		return null;
	}

	return (
		<PanelBody
			title={ __( 'Preview', 'speechkit' ) }
			initialOpen={ true }
			className="beyondwords beyondwords-sidebar__preview"
		>
			<ErrorNotice wrapper={ PanelRow } />
			<PlayAudio wrapper={ PanelRow } />
		</PanelBody>
	);
}

export default PreviewPanel;
