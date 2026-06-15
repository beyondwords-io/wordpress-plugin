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

// The inline Document Settings + Pre-publish panels render no brand icon — the
// registered-plugin icon sits misaligned beside their titles.
registerPlugin( 'beyondwords-document-sidebar', {
	render: DocumentSettingPanel,
} );

registerPlugin( 'beyondwords-prepublish-sidebar', {
	render: PrepublishPanel,
} );

// The plugin sidebar keeps the brand icon for its editor-toolbar pin.
registerPlugin( 'beyondwords-plugin-sidebar', {
	icon: <BeyondwordsIcon />,
	render: Sidebar,
} );
