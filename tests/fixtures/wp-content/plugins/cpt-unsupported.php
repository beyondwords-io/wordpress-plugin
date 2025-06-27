<?php
/**
 * CPT Unsupported
 *
 * @package           BeyondWords
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       CPT Unsupported
 * Description:       Adds an <em>Unsupported</em> custom post type - it does not have support for Custom Fields.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

function cpt_unsupported_init() {
    register_post_type('cpt_unsupported', [
        'graphql_single_name' => 'cptUnsupportedPost',
        'graphql_plural_name' => 'cptUnsupportedPosts',
        'public'              => true,
        'label'               => 'CPT Unsupported',
        'show_in_graphql'     => true,
        'show_in_rest'        => true,
        'supports'            => ['title', 'editor'],
    ]);
}
add_action('init', 'cpt_unsupported_init');
