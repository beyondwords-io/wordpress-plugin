<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Summarization
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.3.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Summarization;

use Beyondwords\Wordpress\Core\Environment;

/**
 * "Summarization" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 5.3.0
 */
class Summarization
{
    /**
     * Init
     *
     * @since 5.3.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        add_action('admin_init', [self::class, 'addSettingsSection'], 5);
    }

    /**
     * Add Settings sections.
     *
     * @since 5.3.0
     * @since 6.0.0 Make static.
     */
    public static function addSettingsSection()
    {
        add_settings_section(
            'summarization',
            __('Summarization', 'speechkit'),
            [self::class, 'sectionCallback'],
            'beyondwords_summarization',
        );
    }

    /**
     * Section callback
     *
     * @since 5.3.0
     * @since 6.0.0 Make static.
     *
     * @return void
     **/
    public static function sectionCallback()
    {
        $linkUrl = sprintf(
            '%s/dashboard/project/%s/settings?tab=summarization',
            Environment::getDashboardUrl(),
            get_option('beyondwords_project_id'),
        );
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'Generate summarized versions of your audio articles.',
                'speechkit'
            );
            ?>
        </p>
        <p class="description">
            <a href="<?php echo esc_url($linkUrl); ?>" target="_blank" class="button button-primary">
                <?php esc_html_e('Manage summarization', 'speechkit'); ?>
            </a>
        </p>
        <?php
    }
}
