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

// Suppress the default plugin icon rendered after the title (a generic "plug") —
// the brand icon already sits before the label via <BeyondwordsTitle />.
const NoIcon = () => null;

export default class PrepublishPanel extends Component {
	// Only the "Generate audio" control; Voice and Player settings live in the plugin sidebar.
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
