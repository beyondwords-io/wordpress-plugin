/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ErrorNotice from '../error-notice';
import PlayerPreview from '../play-audio/preview';

export function PreviewPanel() {
	// The panel is always present and never empty: it surfaces any error, and
	// shows the player once it can load, otherwise a placeholder. PlayerPreview
	// is the same player/placeholder method the document-settings panel uses.
	return (
		<PanelBody
			title={ __( 'Preview', 'speechkit' ) }
			initialOpen={ true }
			className="beyondwords beyondwords-sidebar__preview"
		>
			<ErrorNotice wrapper={ PanelRow } />
			<PlayerPreview wrapper={ PanelRow } />
		</PanelBody>
	);
}

export default PreviewPanel;
