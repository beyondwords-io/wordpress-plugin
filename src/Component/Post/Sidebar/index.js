/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import DocumentSettingPanel from '../../Plugin/Panel/DocumentSetting';
import PrepublishPanel from '../../Plugin/Panel/Prepublish';
import Sidebar from '../../Plugin/Sidebar';

registerPlugin( 'beyondwords-document-sidebar', {
	icon: 'controls-volumeon',
	render: DocumentSettingPanel,
} );

registerPlugin( 'beyondwords-plugin-sidebar', {
	icon: 'controls-volumeon',
	render: Sidebar,
} );

registerPlugin( 'beyondwords-prepublish-sidebar', {
	icon: 'controls-volumeon',
	render: PrepublishPanel,
} );
