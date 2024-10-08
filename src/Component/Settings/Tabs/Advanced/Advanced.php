<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Advanced
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Advanced;

use Beyondwords\Wordpress\Component\Settings\Fields\Languages\Languages;

/**
 * "Advanced" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 5.0.0
 */
class Advanced
{
    /**
     * API client.
     *
     * @since 5.0.0
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @since 5.0.0
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Init
     *
     * @since 5.0.0
     */
    public function init()
    {
        (new Languages($this->apiClient))->init();

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
            'advanced',
            __('Advanced', 'speechkit'),
            '__return_empty_string',
            'beyondwords_advanced',
        );
    }
}
