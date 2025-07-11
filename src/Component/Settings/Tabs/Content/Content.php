<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Content
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Content;

use Beyondwords\Wordpress\Component\Settings\Fields\AutoPublish\AutoPublish;
use Beyondwords\Wordpress\Component\Settings\Fields\IncludeExcerpt\IncludeExcerpt;
use Beyondwords\Wordpress\Component\Settings\Fields\IncludeTitle\IncludeTitle;
use Beyondwords\Wordpress\Component\Settings\Fields\PreselectGenerateAudio\PreselectGenerateAudio;

/**
 * "Content" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 5.0.0
 */
class Content
{
    /**
     * Init
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        (new IncludeTitle())::init();
        (new AutoPublish())::init();
        (new IncludeExcerpt())::init();
        (new PreselectGenerateAudio())::init();

        add_action('admin_init', array(__CLASS__, 'addSettingsSection'), 5);
    }

    /**
     * Add Settings sections.
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     */
    public static function addSettingsSection()
    {
        add_settings_section(
            'content',
            __('Content', 'speechkit'),
            array(__CLASS__, 'sectionCallback'),
            'beyondwords_content',
        );
    }

    /**
     * Section callback
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     *
     * @return void
     **/
    public static function sectionCallback()
    {
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'Only future content will be affected. To apply changes to existing content, please regenerate each post.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }
}
