<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > General
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Content;

use Beyondwords\Wordpress\Component\Settings\Fields\IncludeExcerpt\IncludeExcerpt;
use Beyondwords\Wordpress\Component\Settings\Fields\IncludeTitle\IncludeTitle;
use Beyondwords\Wordpress\Component\Settings\Fields\PreselectGenerateAudio\PreselectGenerateAudio;

/**
 * "General" tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 4.8.0
 */
class Content
{
    /**
     * Constructor.
     *
     * @since 4.8.0
     */
    public function __construct()
    {
    }

    /**
     * Init
     */
    public function init()
    {
        (new IncludeTitle())->init();
        (new IncludeExcerpt())->init();
        (new PreselectGenerateAudio())->init();

        add_action('admin_init', array($this, 'addSettingsSections'));
    }

    /**
     * Add Settings sections.
     *
     * @since  4.8.0
     */
    public function addSettingsSections()
    {
        add_settings_section(
            'content',
            __('Content', 'speechkit'),
            array($this, 'sectionCallback'),
            'beyondwords',
            [
                'before_section' => '<div id="content" data-tab="content">',
                'after_section' => '</div>',
            ]
        );
    }

    /**
     * Section callback
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function sectionCallback()
    {
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'Description here...', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }
}
