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
import ErrorNotice from '../../components/error-notice';
import GenerateAudio from '../../components/generate-audio';
import OpenSidebar from '../../components/open-sidebar';
import PendingNotice from '../../components/pending-notice';
import PlayAudio from '../../components/play-audio';
import SelectVoice from '../../components/select-voice';
import PlayerContent from '../../components/player-content';
import PlayerStyle from '../../components/player-style';

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
