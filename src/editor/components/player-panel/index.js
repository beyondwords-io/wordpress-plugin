/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ContentId from '../content-id';
import DisplayPlayer from '../display-player';
import ErrorNotice from '../error-notice';
import GenerateAudio from '../generate-audio';
import PendingNotice from '../pending-notice';
import PlayAudio from '../play-audio';
import PlayerContent from '../player-content';
import PlayerStyle from '../player-style';
import SelectVoice from '../select-voice';

export function PlayerPanel() {
	return (
		<PanelBody
			title={ __( 'Player', 'speechkit' ) }
			opened={ true }
			className={ `beyondwords beyondwords-sidebar__status` }
		>
			<ErrorNotice wrapper={ PanelRow } />
			<PendingNotice wrapper={ PanelRow } />
			<PlayAudio wrapper={ PanelRow } />
			<ContentId wrapper={ PanelRow } />
			<hr />
			<GenerateAudio wrapper={ PanelRow } />
			<DisplayPlayer wrapper={ PanelRow } />
			<hr />
			<PlayerStyle wrapper={ PanelRow } />
			<PlayerContent wrapper={ PanelRow } />
			<SelectVoice wrapper={ PanelRow } />
		</PanelBody>
	);
}

export default PlayerPanel;
