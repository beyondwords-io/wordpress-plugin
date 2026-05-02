/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import DocumentSettingPanel from '../../editor/document-setting';
import PrepublishPanel from '../../editor/prepublish';
import Sidebar from '../../editor/sidebar';

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
