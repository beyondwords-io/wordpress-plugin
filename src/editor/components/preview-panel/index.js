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
import { canPlayAudio } from '../play-audio/helpers';

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
	// Show the panel when PlayAudio will render a player *or* there's an error to
	// surface. canPlayAudio matches PlayAudioCheck exactly, so the panel never
	// renders an empty body — e.g. a "Pending" post that has content but no
	// player, and no error, stays hidden.
	const showPanel = useSelect(
		( select ) => canPlayAudio( select ) || hasError( select ),
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
