/**
 * WordPress dependencies
 */
import { PanelRow } from '@wordpress/components';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BeyondwordsTitle } from '../../components/icon';
import ErrorNotice from '../../components/error-notice';
import GenerateAudio from '../../components/generate-audio';
import OpenSidebar from '../../components/open-sidebar';
import PendingNotice from '../../components/pending-notice';
import PlayerPreview from '../../components/play-audio/preview';
import Stack from '../../components/stack';

export default class DocumentSettingPanel extends Component {
	// The Voice (Customize/Language/Voice) and Player (Embed) settings are
	// exposed only in the plugin sidebar; this panel keeps the "Generate audio"
	// control plus the link to open that sidebar.
	render() {
		return (
			<PluginDocumentSettingPanel
				name="beyondwords-document-settings-panel"
				title={ <BeyondwordsTitle /> }
				className="beyondwords-sidebar"
			>
				<Stack>
					<ErrorNotice wrapper={ PanelRow } />
					<PendingNotice wrapper={ PanelRow } />
					<PlayerPreview wrapper={ PanelRow } />
					<GenerateAudio wrapper={ PanelRow } />
					<hr />
					<OpenSidebar wrapper={ PanelRow } />
				</Stack>
			</PluginDocumentSettingPanel>
		);
	}
}
