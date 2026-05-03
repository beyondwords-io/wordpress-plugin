/**
 * BeyondWords block editor JS bundle entry point.
 *
 * Each `require` pulls in a self-registering module — when bundled by
 * `wp-scripts build`, side effects in those modules wire up filters and
 * components.
 */

// Settings page (REST + admin UI).
require( './settings' );

// Block-editor JS bootstrap — registers the document-setting, sidebar and
// prepublish plugin slots via @wordpress/plugins. Each slot's component is
// imported transitively from src/editor/block/{slot}/index.js.
require( './editor/block' );

// Components — reusable PHP+JS UI bits consumed by the block-editor pages
// (and by the classic-editor metabox in PHP). Each component's `index.js`
// self-registers any block-editor filters or sidebar mounts it owns.
require( './editor/components/add-player' );
require( './editor/components/block-attributes' );
require( './editor/components/display-player' );
require( './editor/components/error-notice' );
require( './editor/components/generate-audio' );
require( './editor/components/help-panel' );
require( './editor/components/inspect-panel' );
require( './editor/components/open-sidebar' );
require( './editor/components/pending-notice' );
require( './editor/components/play-audio' );
require( './editor/components/prepublish-panel' );
