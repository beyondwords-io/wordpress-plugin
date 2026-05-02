/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import HelpPanel from '../../post/help-panel';
import InspectPanel from '../../post/inspect-panel';
import PlayerPanel from '../../post/player-panel';

export default class Sidebar extends Component {
	render() {
		return (
			<Fragment>
				<PluginSidebarMoreMenuItem target="plugin-sidebar">
					{ __( 'BeyondWords', 'speechkit' ) }
				</PluginSidebarMoreMenuItem>
				<PluginSidebar
					name="plugin-sidebar"
					title={ __( 'BeyondWords', 'speechkit' ) }
				>
					<PlayerPanel />
					<HelpPanel />
					<InspectPanel />
				</PluginSidebar>
			</Fragment>
		);
	}
}
