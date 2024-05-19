<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > General
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\General;

use Beyondwords\Wordpress\Component\Settings\Fields\ApiKey\ApiKey;
use Beyondwords\Wordpress\Component\Settings\Fields\ProjectId\ProjectId;
use Beyondwords\Wordpress\Component\Settings\Fields\SettingsUpdated\SettingsUpdated;
use Beyondwords\Wordpress\Core\Environment;

/**
 * "General" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 4.8.0
 */
class General
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
        (new ApiKey())->init();
        (new ProjectId())->init();
        (new SettingsUpdated())->init();

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
            'beyondwords_general',
            // [
            //     'before_section' => '<div id="general" data-tab="general">' . $this->dashboardLink(),
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
        delete_transient('beyondwords_settings_errors');

        ?>
        <p class="description">
            <?php
            esc_html_e(
                'The details we need to authenticate your BeyondWords account. For more options, head to your BeyondWords dashboard.', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }

    /**
     * @since 3.0.0
     * @since 4.8.0 Moved from Settings/Settings to Settings/Tabs/General.
     *
     * @return string
     */
    public function dashboardLink()
    {
        $projectId = get_option('beyondwords_project_id');

        if ($projectId) :
            ob_start();
            ?>
            <p>
                <a
                    class="button button-secondary"
                    href="<?php echo esc_url(Environment::getDashboardUrl()); ?>"
                    target="_blank"
                >
                    <?php esc_html_e('BeyondWords dashboard', 'speechkit'); ?>
                </a>
            </p>
            <?php
            return ob_get_clean();
        endif;

        return '';
    }
}
