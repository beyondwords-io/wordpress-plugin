/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow } from '@wordpress/components';

/**
 * Internal dependencies
 */
import DisplayPlayer from '../../DisplayPlayer';
import ErrorNotice from '../../ErrorNotice';
import GenerateAudio from '../../GenerateAudio';
import PendingNotice from '../../PendingNotice';
import PlayAudio from '../../PlayAudio';
import PlayerStyle from '../../PlayerStyle';
import SelectVoice from '../../SelectVoice';

export function StatusPanel() {
	return (
		<PanelBody
			title={ __( 'Status', 'speechkit' ) }
			opened={ true }
			className={ `beyondwords beyondwords-sidebar__status` }
		>
			<GenerateAudio wrapper={ PanelRow } />
			<PendingNotice wrapper={ PanelRow } />
			<PlayAudio wrapper={ PanelRow } />
			<DisplayPlayer wrapper={ PanelRow } />
			<PlayerStyle wrapper={ PanelRow } />
			<SelectVoice wrapper={ PanelRow } />
			<ErrorNotice wrapper={ PanelRow } />
		</PanelBody>
	);
}

export default StatusPanel;
