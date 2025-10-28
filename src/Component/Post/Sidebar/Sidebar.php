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
 * Sidebar
 *
 * @since 3.0.0
 */
class Sidebar
{
    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        add_action('enqueue_block_assets', [self::class, 'enqueueBlockAssets']);
    }

    /**
     * Enqueue Block Editor assets.
     *
     * @since 6.0.0 Make static.
     */
    public static function enqueueBlockAssets()
    {
        if (CoreUtils::isGutenbergPage()) {
            $postType = get_post_type();

            $postTypes = SettingsUtils::getCompatiblePostTypes();

            if (in_array($postType, $postTypes)) {
                // Register the Block Editor "Sidebar" CSS
                wp_enqueue_style(
                    'beyondwords-Sidebar',
                    BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/Sidebar/PostSidebar.css',
                    [],
                    BEYONDWORDS__PLUGIN_VERSION
                );
            }
        }
    }
}
