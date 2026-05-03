/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import DocumentSettingPanel from './document-setting';
import PrepublishPanel from './prepublish';
import Sidebar from './sidebar';

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
