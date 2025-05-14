<?php
/**
 * CPT Inactive
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       CPT Inactive
 * Description:       Adds an <em>Inactive</em> custom post type - it is supported but IT WON'T be checked in the plugin settings.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

function cpt_inactive_init() {
    register_post_type('cpt_inactive', [
        'graphql_single_name' => 'cptInactivePost',
        'graphql_plural_name' => 'cptInactivePosts',
        'public'              => true,
        'label'               => 'CPT Inactive',
        'show_in_graphql'     => true,
        'show_in_rest'        => true,
        'supports'            => ['title', 'editor', 'custom-fields'],
    ]);
}
add_action('init', 'cpt_inactive_init');
