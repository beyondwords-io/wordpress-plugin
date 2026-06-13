/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow } from '@wordpress/components';

/**
 * Internal dependencies
 */
import PlayAudio from '../play-audio';
import { useCanPlayAudio } from '../play-audio/hooks';

export function PreviewPanel() {
	// Gate the panel on the same predicate PlayAudioCheck uses, so the panel
	// never renders an empty body when PlayAudio would render nothing (e.g. a
	// post still in "Pending" review, or legacy meta missing a project id).
	const canPreview = useCanPlayAudio();

	if ( ! canPreview ) {
		return null;
	}

	return (
		<PanelBody
			title={ __( 'Preview', 'speechkit' ) }
			initialOpen={ true }
			className="beyondwords beyondwords-sidebar__preview"
		>
			<PlayAudio wrapper={ PanelRow } />
		</PanelBody>
	);
}

export default PreviewPanel;
