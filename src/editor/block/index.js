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
import { BeyondwordsIcon } from '../components/icon';

registerPlugin( 'beyondwords-document-sidebar', {
	icon: <BeyondwordsIcon />,
	render: DocumentSettingPanel,
} );

registerPlugin( 'beyondwords-prepublish-sidebar', {
	icon: <BeyondwordsIcon />,
	render: PrepublishPanel,
} );

registerPlugin( 'beyondwords-plugin-sidebar', {
	icon: <BeyondwordsIcon />,
	render: Sidebar,
} );
