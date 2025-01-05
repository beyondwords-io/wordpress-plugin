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
import ErrorNotice from '../../../Post/ErrorNotice';
import GenerateAudio from '../../../Post/GenerateAudio';
import OpenSidebar from '../../../Post/OpenSidebar';
import PendingNotice from '../../../Post/PendingNotice';
import PlayAudio from '../../../Post/PlayAudio';
import SelectVoice from '../../../Post/SelectVoice';
import PlayerContent from '../../../Post/PlayerContent';
import PlayerStyle from '../../../Post/PlayerStyle';

export default class DocumentSettingPanel extends Component {
	render() {
		return (
			<PluginDocumentSettingPanel
				name="beyondwords-document-settings-panel"
				title={ __( 'BeyondWords', 'speechkit' ) }
				className="beyondwords-sidebar"
			>
				<GenerateAudio wrapper={ PanelRow } />
				<ErrorNotice wrapper={ PanelRow } />
				<PendingNotice wrapper={ PanelRow } />
				<PlayAudio wrapper={ PanelRow } />
				<PlayerStyle wrapper={ PanelRow } />
				<PlayerContent wrapper={ PanelRow } />
				<SelectVoice wrapper={ PanelRow } />
				<OpenSidebar wrapper={ PanelRow } />
			</PluginDocumentSettingPanel>
		);
	}
}
