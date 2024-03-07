<?php

declare(strict_types=1);

/**
 * BeyondWords Post Inspect Panel.
 *
 * Text Domain: beyondwords
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Post\Sidebar;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * Sidebar setup
 *
 * @since 3.0.0
 */
class Sidebar
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('enqueue_block_assets', array($this, 'enqueueBlockAssets'));
    }

    public function enqueueBlockAssets()
    {
        if (CoreUtils::isGutenbergPage()) {
            $postType = get_post_type();

            $postTypes = SettingsUtils::getCompatiblePostTypes();

            if (in_array($postType, $postTypes)) {
                // Register the Block Editor "Sidebar" CSS
                wp_enqueue_style(
                    'beyondwords-Sidebar',
                    BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/Sidebar/PostSidebar.css',
                    array(),
                    BEYONDWORDS__PLUGIN_VERSION
                );
            }
        }
    }
}
