<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Credentials
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Credentials;

use Beyondwords\Wordpress\Component\Settings\Fields\ApiKey\ApiKey;
use Beyondwords\Wordpress\Component\Settings\Fields\ProjectId\ProjectId;

/**
 * "Credentials" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 4.8.0
 */
class Credentials
{
    /**
     * Init
     */
    public function init()
    {
        (new ApiKey())->init();
        (new ProjectId())->init();

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
            'credentials',
            __('Credentials', 'speechkit'),
            array($this, 'sectionCallback'),
            'beyondwords_credentials',
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
                'Please add your Project ID and API key to authenticate your BeyondWords account.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }
}
