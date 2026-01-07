<?php

declare(strict_types=1);

/**
 * BeyondWords Post ErrorNotice.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Post\ErrorNotice;

use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * ErrorNotice
 *
 * @since 3.0.0
 */
defined('ABSPATH') || exit;

class ErrorNotice
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
        // Only enqueue for Gutenberg screens
        if (CoreUtils::isGutenbergPage()) {
            // Register the Block Editor "Error Notice" CSS
            wp_enqueue_style(
                'beyondwords-ErrorNotice',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/ErrorNotice/error-notice.css',
                [],
                BEYONDWORDS__PLUGIN_VERSION
            );
        }
    }
}
