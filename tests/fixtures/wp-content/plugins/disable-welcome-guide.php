<?php
/**
 * Disable welcome guide.
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Disable welcome guide
 * Description:       Prevent the Welcome Guide from appearing during tests.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

 add_action(
	'enqueue_block_editor_assets',
	function () {
        $cmd = [
			'wp.data.dispatch("core/preferences").set("core/edit-post", "welcomeGuide", false)',
			'wp.data.dispatch("core/preferences").set("core/edit-post", "welcomeGuideTemplate", false)',
			'wp.data.dispatch("core/preferences").set("core/edit-widgets", "welcomeGuide", false)',
			'wp.data.dispatch("core/preferences").set("core/edit-widgets", "welcomeGuideTemplate", false)',
		];

		// wp_add_inline_script(
        //     'wp-data',
        //     sprintf( "window.onload = function() { %s; alert('wp-data'); };", join(';', $cmd ) ),
        // );
	},
	999
);
