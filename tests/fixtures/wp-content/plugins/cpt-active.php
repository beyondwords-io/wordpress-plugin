<?php
/**
 * CPT Active
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       CPT Active
 * Description:       Adds an <em>Active</em> custom post type - it is supported and IT WILL be checked in the plugin settings.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

function cpt_active_init() {
    register_post_type('cpt_active', [
        'graphql_single_name' => 'cptActivePost',
        'graphql_plural_name' => 'cptActivePosts',
        'public'              => true,
        'label'               => 'CPT Active',
        'show_in_graphql'     => true,
        'show_in_rest'        => true,
        'supports'            => ['title', 'editor', 'custom-fields'],
    ]);
}
add_action('init', 'cpt_active_init');
