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
import { PlayerSection, VoiceSection } from '../../components/settings-panel';
import Stack from '../../components/stack';

export default class DocumentSettingPanel extends Component {
	render() {
		return (
			<PluginDocumentSettingPanel
				name="beyondwords-document-settings-panel"
				title={ __( 'BeyondWords', 'speechkit' ) }
				className="beyondwords-sidebar"
			>
				<Stack>
					<ErrorNotice wrapper={ PanelRow } />
					<PendingNotice wrapper={ PanelRow } />
					<PlayAudio wrapper={ PanelRow } />
					<GenerateAudio wrapper={ PanelRow } />
					<hr />
					<VoiceSection withPanel={ false } />
					<hr />
					<PlayerSection withPanel={ false } />
					<hr />
					<OpenSidebar wrapper={ PanelRow } />
				</Stack>
			</PluginDocumentSettingPanel>
		);
	}
}
