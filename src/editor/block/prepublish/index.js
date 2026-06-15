/**
 * WordPress dependencies
 */
import { PanelRow } from '@wordpress/components';
import { PluginPrePublishPanel } from '@wordpress/editor';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BeyondwordsTitle } from '../../components/icon';
import ErrorNotice from '../../components/error-notice';
import GenerateAudio from '../../components/generate-audio';
import Stack from '../../components/stack';

// The pre-publish panel renders the registered plugin's icon after the title by
// default (a generic "plug"). Suppress it with a no-op — the brand icon already
// sits before the label via <BeyondwordsTitle />.
const NoIcon = () => null;

export default class PrepublishPanel extends Component {
	// Matches the Document Settings panel: only the "Generate audio" control.
	// The Voice (Customize/Language/Voice) and Player (Embed) settings live in
	// the plugin sidebar.
	render() {
		return (
			<PluginPrePublishPanel
				name="beyondwords-prepublish-panel"
				title={ <BeyondwordsTitle /> }
				icon={ NoIcon }
				initialOpen={ true }
				className="beyondwords-sidebar"
			>
				<Stack>
					<ErrorNotice wrapper={ PanelRow } />
					<GenerateAudio wrapper={ PanelRow } />
				</Stack>
			</PluginPrePublishPanel>
		);
	}
}
