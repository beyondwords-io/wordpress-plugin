/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BeyondwordsIcon } from '../../components/icon';
import DataPanel from '../../components/data-panel';
import HelpPanel from '../../components/help-panel';
import InspectPanel from '../../components/inspect-panel';
import PreviewPanel from '../../components/preview-panel';
import SettingsPanel from '../../components/settings-panel';

export default class Sidebar extends Component {
	render() {
		return (
			<Fragment>
				<PluginSidebarMoreMenuItem
					target="plugin-sidebar"
					icon={ <BeyondwordsIcon /> }
				>
					{ __( 'BeyondWords', 'speechkit' ) }
				</PluginSidebarMoreMenuItem>
				{ /*
				   `title` must stay a plain string: it becomes the pinned toolbar
				   button's aria-label and tooltip, where an element stringifies to
				   "[object Object]". The header's brand mark is drawn in CSS instead.
				*/ }
				<PluginSidebar
					name="plugin-sidebar"
					title={ __( 'BeyondWords', 'speechkit' ) }
					icon={ <BeyondwordsIcon /> }
					headerClassName="beyondwords-sidebar__header"
				>
					<PreviewPanel />
					<SettingsPanel />
					<HelpPanel />
					<DataPanel />
					<InspectPanel />
				</PluginSidebar>
			</Fragment>
		);
	}
}
