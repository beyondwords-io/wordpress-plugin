/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ErrorNotice from '../error-notice';
import PlayAudio from '../play-audio';
import PlayerPlaceholder from '../play-audio/placeholder';
import { useHasPlayAudioAction } from '../play-audio/hooks';

export function PreviewPanel() {
	// The panel is always present and never empty: it surfaces any error, and
	// shows the live player once it can load, otherwise a placeholder.
	const canPreview = useHasPlayAudioAction();

	return (
		<PanelBody
			title={ __( 'Preview', 'speechkit' ) }
			initialOpen={ true }
			className="beyondwords beyondwords-sidebar__preview"
		>
			<ErrorNotice wrapper={ PanelRow } />
			{ canPreview ? (
				<PlayAudio wrapper={ PanelRow } />
			) : (
				<PlayerPlaceholder />
			) }
		</PanelBody>
	);
}

export default PreviewPanel;
