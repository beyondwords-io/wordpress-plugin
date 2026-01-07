<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Credentials
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Credentials;

use Beyondwords\Wordpress\Component\Settings\Fields\ApiKey\ApiKey;
use Beyondwords\Wordpress\Component\Settings\Fields\ProjectId\ProjectId;

/**
 * "Credentials" settings tab
 *
 * @since 5.0.0
 */
defined('ABSPATH') || exit;

class Credentials
{
    /**
     * Init
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        (new ApiKey())::init();
        (new ProjectId())::init();

        add_action('admin_init', [self::class, 'addSettingsSection'], 5);
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
            'credentials',
            __('Credentials', 'speechkit'),
            [self::class, 'sectionCallback'],
            'beyondwords_credentials',
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
                'Please add your Project ID and API key to authenticate your BeyondWords account.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }
}
