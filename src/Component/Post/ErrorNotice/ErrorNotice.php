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
class ErrorNotice
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
        // Only enqueue for Gutenberg screens
        if (CoreUtils::isGutenbergPage()) {
            // Register the Block Editor "Error Notice" CSS
            wp_enqueue_style(
                'beyondwords-ErrorNotice',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/ErrorNotice/error-notice.css',
                array(),
                BEYONDWORDS__PLUGIN_VERSION
            );
        }
    }
}
