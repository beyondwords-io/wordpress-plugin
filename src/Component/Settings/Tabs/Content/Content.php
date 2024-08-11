<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Content
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Content;

use Beyondwords\Wordpress\Component\Settings\Fields\IncludeExcerpt\IncludeExcerpt;
use Beyondwords\Wordpress\Component\Settings\Fields\IncludeTitle\IncludeTitle;
use Beyondwords\Wordpress\Component\Settings\Fields\PreselectGenerateAudio\PreselectGenerateAudio;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * "Content" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 4.8.0
 */
class Content
{
    /**
     * @var \Beyondwords\Wordpress\Core\ApiClient
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @since 4.8.0
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Init
     */
    public function init()
    {
        (new IncludeTitle($this->apiClient))->init();
        (new IncludeExcerpt())->init();
        (new PreselectGenerateAudio())->init();

        add_action('admin_init', array($this, 'addSettingsSection'), 5);
    }

    /**
     * Add Settings sections.
     *
     * @since  4.8.0
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
                'Only future content will be affected. To apply changes to existing content, please regenerate each post.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }
}
