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
     */
    public function init()
    {
        (new IncludeTitle())->init();
        (new IncludeExcerpt())->init();
        (new PreselectGenerateAudio())->init();

        add_action('admin_init', array($this, 'addSettingsSection'), 5);
    }

    /**
     * Add Settings sections.
     *
     * @since 5.0.0
     */
    public function addSettingsSection()
    {
        add_settings_section(
            'content',
            __('Content', 'speechkit'),
            array($this, 'sectionCallback'),
            'beyondwords_content',
        );
    }

    /**
     * Section callback
     *
     * @since 5.0.0
     *
     * @return void
     **/
    public function sectionCallback()
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
