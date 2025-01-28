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
import ErrorNotice from '../../../Post/ErrorNotice';
import GenerateAudio from '../../../Post/GenerateAudio';
import PlayerContent from '../../../Post/PlayerContent';
import PlayerStyle from '../../../Post/PlayerStyle';
import SelectVoice from '../../../Post/SelectVoice';

export default class PrepublishPanel extends Component {
	render() {
		return (
			<PluginPrePublishPanel
				name="beyondwords-prepublish-panel"
				title={ __( 'BeyondWords', 'speechkit' ) }
				initialOpen={ true }
				className="beyondwords-sidebar"
			>
				<GenerateAudio wrapper={ PanelRow } />
				<PlayerStyle wrapper={ PanelRow } />
				<PlayerContent wrapper={ PanelRow } />
				<SelectVoice wrapper={ PanelRow } />
				<ErrorNotice wrapper={ PanelRow } />
			</PluginPrePublishPanel>
		);
	}
}
