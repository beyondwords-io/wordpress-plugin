/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelRow } from '@wordpress/components';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ErrorNotice from '../../post/error-notice';
import GenerateAudio from '../../post/generate-audio';
import OpenSidebar from '../../post/open-sidebar';
import PendingNotice from '../../post/pending-notice';
import PlayAudio from '../../post/play-audio';
import SelectVoice from '../../post/select-voice';
import PlayerContent from '../../post/player-content';
import PlayerStyle from '../../post/player-style';

export default class DocumentSettingPanel extends Component {
	render() {
		return (
			<PluginDocumentSettingPanel
				name="beyondwords-document-settings-panel"
				title={ __( 'BeyondWords', 'speechkit' ) }
				className="beyondwords-sidebar"
			>
				<ErrorNotice wrapper={ PanelRow } />
				<PendingNotice wrapper={ PanelRow } />
				<PlayAudio wrapper={ PanelRow } />
				<GenerateAudio wrapper={ PanelRow } />
				<hr />
				<PlayerStyle wrapper={ PanelRow } />
				<PlayerContent wrapper={ PanelRow } />
				<SelectVoice wrapper={ PanelRow } />
				<OpenSidebar wrapper={ PanelRow } />
			</PluginDocumentSettingPanel>
		);
	}
}
