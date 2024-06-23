<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > General
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Pronunciations;

use Beyondwords\Wordpress\Core\Environment;

/**
 * "Pronunciations" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 4.8.0
 */
class Pronunciations
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
            'pronunciations',
            __('Pronunciations', 'speechkit'),
            array($this, 'sectionCallback'),
            'beyondwords_pronunciations',
            // [
            //     'before_section' => '<div id="pronunciations" data-tab="pronunciations">',
            //     'after_section' => '</div>',
            // ]
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
        $rulesUrl = sprintf(
            '%s/dashboard/project/%s/settings?tab=rules',
            Environment::getDashboardUrl(),
            get_option('beyondwords_project_id', ''),
        );
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'You can use rules to substitute one word for another, force an acronym to be said as a word or a letter sequence, or provide the phonetic transcription for a word so that it is pronounced exactly the way you want it to be.',  // phpcs:ignore Generic.Files.LineLength.TooLong
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
            <a href="<?php echo esc_url($rulesUrl); ?>" target="_blank" class="button button-secondary">
                <?php esc_html_e('Edit Project Rules', 'speechkit'); ?>
            </a>
            <?php esc_html_e('in your BeyondWords Dashboard.', 'speechkit'); ?>
        </p>
        <?php
    }
}
