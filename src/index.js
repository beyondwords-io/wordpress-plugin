/**
 * BeyondWords block editor JS bundle entry point.
 *
 * Each `require` is a self-registering module: its side effects wire up
 * filters and components when bundled by `wp-scripts build`.
 */

require( './settings' );

require( './editor/block' );

require( './editor/components/add-player' );
require( './editor/components/block-attributes' );
require( './editor/components/data-panel' );
require( './editor/components/error-notice' );
require( './editor/components/generate-audio' );
require( './editor/components/help-panel' );
require( './editor/components/inspect-panel' );
require( './editor/components/open-sidebar' );
require( './editor/components/pending-notice' );
require( './editor/components/play-audio' );
require( './editor/components/preview-panel' );
require( './editor/components/settings-panel' );
