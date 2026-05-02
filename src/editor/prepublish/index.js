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
import ErrorNotice from '../../post/error-notice';
import GenerateAudio from '../../post/generate-audio';
import PlayerContent from '../../post/player-content';
import PlayerStyle from '../../post/player-style';
import SelectVoice from '../../post/select-voice';

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
