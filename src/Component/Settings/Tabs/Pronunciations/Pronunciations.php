<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Pronunciations
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Pronunciations;

use Beyondwords\Wordpress\Core\Environment;

/**
 * "Pronunciations" settings tab
 * @since 5.0.0
 */
class Pronunciations
{
    /**
     * Init
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
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
            'pronunciations',
            __('Pronunciations', 'speechkit'),
            [self::class, 'sectionCallback'],
            'beyondwords_pronunciations',
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
        $rulesUrl = sprintf(
            '%s/dashboard/project/%s/settings?tab=rules',
            Environment::getDashboardUrl(),
            get_option('beyondwords_project_id'),
        );
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'Create a custom pronunciation rule for any word or phrase.',
                'speechkit'
            );
            ?>
        </p>
        <p class="description">
            <a href="<?php echo esc_url($rulesUrl); ?>" target="_blank" class="button button-primary">
                <?php esc_html_e('Manage pronunciations', 'speechkit'); ?>
            </a>
        </p>
        <?php
    }
}
