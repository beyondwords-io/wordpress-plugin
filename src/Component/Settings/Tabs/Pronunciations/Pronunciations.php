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
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 5.0.0
 */
class Pronunciations
{
    /**
     * Init
     *
     * @since 5.0.0
     */
    public function init()
    {
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
            'pronunciations',
            __('Pronunciations', 'speechkit'),
            array($this, 'sectionCallback'),
            'beyondwords_pronunciations',
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
        <!-- <p class="description">
            <?php
            esc_html_e(
                'Go to the Settings section in your project, select the Rules tab, here you can see a list of rules, create new ones, update or delete existing ones.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p> -->
        <p class="description">
            <a href="<?php echo esc_url($rulesUrl); ?>" target="_blank" class="button button-primary">
                <?php esc_html_e('Manage pronunciations', 'speechkit'); ?>
            </a>
        </p>
        <?php
    }
}
