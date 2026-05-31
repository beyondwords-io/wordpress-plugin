/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelRow } from '@wordpress/components';
import { PluginPrePublishPanel } from '@wordpress/editor';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ErrorNotice from '../../components/error-notice';
import GenerateAudio from '../../components/generate-audio';
import { PlayerSection, VoiceSection } from '../../components/settings-panel';
import Stack from '../../components/stack';

export default class PrepublishPanel extends Component {
	render() {
		return (
			<PluginPrePublishPanel
				name="beyondwords-prepublish-panel"
				title={ __( 'BeyondWords', 'speechkit' ) }
				initialOpen={ true }
				className="beyondwords-sidebar"
			>
				<Stack>
					<ErrorNotice wrapper={ PanelRow } />
					<GenerateAudio wrapper={ PanelRow } />
					<hr />
					<VoiceSection withPanel={ false } />
					<hr />
					<PlayerSection withPanel={ false } />
				</Stack>
			</PluginPrePublishPanel>
		);
	}
}
