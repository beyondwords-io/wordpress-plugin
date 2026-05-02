<?php

declare(strict_types=1);

/**
 * BeyondWords SiteHealth.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.7.0
 */

namespace Beyondwords\Wordpress\Component\SiteHealth;

use BeyondWords\Settings\Fields as SettingsFields;
use BeyondWords\Settings\Utils as SettingsUtils;
use Beyondwords\Wordpress\Core\Environment;

/**
 * BeyondWords SiteHealth.
 *
 * @since 3.7.0
 */
defined('ABSPATH') || exit;

class SiteHealth
{
    /**
     * @var string[] List of current filters to check.
     *
     * @since  3.7.0 Introduced.
     * @since  4.3.0 Filters refactoring - many were removed and renamed.
     */
    public const FILTERS = [
        'beyondwords_content_params',
        'beyondwords_player_script_onload',
        'beyondwords_player_html',
        'beyondwords_player_sdk_params',
        'beyondwords_settings_player_styles',
        'beyondwords_settings_post_types',
        'beyondwords_settings_post_statuses',
    ];

    /**
     * @var string[] List of deprecated filters to check.
     *
     * @since  3.7.0 Introduced.
     * @since  4.3.0 Filters refactoring - many were removed and renamed.
     */
    public const DEPRECATED_FILTERS = [
        'beyondwords_amp_player_html',
        'beyondwords_body_params',
        'beyondwords_content',
        'beyondwords_content_id',
        'beyondwords_js_player_html',
        'beyondwords_js_player_params',
        'beyondwords_player_styles',
        'beyondwords_post_audio_enabled_blocks',
        'beyondwords_post_metadata',
        'beyondwords_post_player_enabled',
        'beyondwords_post_statuses',
        'beyondwords_post_types',
        'beyondwords_project_id',
        'sk_player_after',
        'sk_player_before',
        'sk_the_content',
        'speechkit_amp_player_html',
        'speechkit_content',
        'speechkit_js_player_html',
        'speechkit_js_player_params',
        'speechkit_post_player_enabled',
        'speechkit_post_statuses',
        'speechkit_post_types',
    ];

    /**
     * Init
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        add_filter('debug_information', [self::class, 'debugInformation']);
    }

    /**
     * Add "Site Health" navigation tab.
     *
     * @since 3.7.0
     * @since 6.0.0 Make static.
     *
     *
     */
    public static function debugInformation($info)
    {
        $info['beyondwords']['label'] = __('BeyondWords - Text-to-Speech', 'speechkit');

        self::addPluginVersion($info);
        self::addRestApiConnection($info);

        $info['beyondwords']['fields']['compatible-post-types'] = [
            'label' => __('Compatible post types', 'speechkit'),
            'value' => implode(', ', SettingsUtils::get_compatible_post_types()),
        ];

        $info['beyondwords']['fields']['integration-method'] = [
            'label' => __('Integration method', 'speechkit'),
            'value' => SettingsFields::get_integration_method(),
        ];

        $info['beyondwords']['fields']['beyondwords_api_key'] = [
            'label' => __('API Key', 'speechkit'),
            'value' => SiteHealth::maskString(get_option('beyondwords_api_key')),
        ];

        $info['beyondwords']['fields']['beyondwords_project_id'] = [
            'label' => __('Project ID', 'speechkit'),
            'value' => get_option('beyondwords_project_id'),
        ];

        self::addPreferences($info);
        self::addFilters($info);
        self::addNoticeSettings($info);

        self::addConstant($info, 'BEYONDWORDS_AUTOREGENERATE');

        return $info;
    }

    /**
     * Add the per-site preferences (Preferences tab) to the debugging array.
     *
     * @since 7.0.0 Replaces addContentSettings/addProjectSettings/addPlayerSettings.
     *
     * @param array $info Debugging info array
     */
    public static function addPreferences(array &$info): void
    {
        $info['beyondwords']['fields']['beyondwords_prepend_excerpt'] = [
            'label' => __('Include excerpt', 'speechkit'),
            'value' => get_option('beyondwords_prepend_excerpt') ? __('Yes', 'speechkit') : __('No', 'speechkit'),
            'debug' => get_option('beyondwords_prepend_excerpt') ? 'yes' : 'no',
        ];

        $info['beyondwords']['fields']['beyondwords_player_ui'] = [
            'label' => __('Player UI', 'speechkit'),
            'value' => get_option('beyondwords_player_ui'),
        ];

        $info['beyondwords']['fields']['beyondwords_preselect'] = [
            'label' => __('Preselect ‘Generate audio’', 'speechkit'),
            'value' => (string) wp_json_encode(get_option('beyondwords_preselect'), JSON_PRETTY_PRINT),
        ];
    }

    /**
     * Add plugin version to the info debugging array.
     *
     * @since 3.7.0
     * @since 6.0.0 Make static.
     *
     * @param array  $info Debugging info array
     *
     * @return array
     */
    public static function addPluginVersion(&$info)
    {
        $constVersion = defined('BEYONDWORDS__PLUGIN_VERSION') ? BEYONDWORDS__PLUGIN_VERSION : '';
        $dbVersion    = get_option('beyondwords_version');

        if ($constVersion && $constVersion === $dbVersion) {
            $info['beyondwords']['fields']['plugin-version'] = [
                'label' => __('Plugin version', 'speechkit'),
                'value' => BEYONDWORDS__PLUGIN_VERSION,
            ];
        } else {
            $info['beyondwords']['fields']['plugin-version'] = [
                'label' => __('Plugin version', 'speechkit'),
                'value' => sprintf(
                    /* translators: 1: Current plugin version, 2: Database plugin version */
                    __('Version mismatch: file: %1$s / db: %2$s', 'speechkit'),
                    $constVersion,
                    $dbVersion
                ),
            ];
        }
    }

    /**
     * Adds debugging data for the BeyondWords REST API connection.
     *
     * @since 3.7.0
     * @since 5.2.2 Remove sslverify param for REST API calls.
     * @since 6.0.0 Make static.
     *
     * @param array  $info Debugging info array
     *
     * @return array
     */
    public static function addRestApiConnection(&$info)
    {
        // translators: Tab heading for Site Health navigation.
        $apiUrl = Environment::getApiUrl();

        $info['beyondwords']['fields']['api-url'] = [
            'label' => __('REST API URL', 'speechkit'),
            'value' => $apiUrl,
        ];

        $response = wp_remote_request(Environment::getApiUrl(), [
            'blocking'    => true,
            'body'        => '',
            'method'      => 'GET',
        ]);

        if (! is_wp_error($response)) {
            $info['beyondwords']['fields']['api-communication'] = [
                'label' => __('Communication with REST API', 'speechkit'),
                'value' => __('BeyondWords API is reachable', 'speechkit'),
                'debug' => 'true',
            ];
        } else {
            $info['beyondwords']['fields']['api-communication'] = [
                'label' => __('Communication with REST API', 'speechkit'),
                'value' => sprintf(
                    /* translators: 1: The IP address the REST API resolves to. 2: The error returned by the lookup. */
                    __('Unable to reach BeyondWords API at %1$s: %2$s', 'speechkit'),
                    gethostbyname(Environment::getApiUrl()),
                    $response->get_error_message()
                ),
                'debug' => $response->get_error_message(),
            ];
        }
    }

    /**
     * Adds filters.
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     *
     * @param array $info Debugging info array
     */
    public static function addFilters(array &$info): void
    {
        $registered = array_values(array_filter(SiteHealth::FILTERS, 'has_filter'));

        $info['beyondwords']['fields']['registered-filters'] = [
            'label' => __('Registered filters', 'speechkit'),
            'value' => empty($registered) ? __('None', 'speechkit') : implode(', ', $registered),
            'debug' => empty($registered) ? 'none' : implode(', ', $registered),
        ];

        $registered = array_values(array_filter(SiteHealth::DEPRECATED_FILTERS, 'has_filter'));

        $info['beyondwords']['fields']['registered-deprecated-filters'] = [
            'label' => __('Registered deprecated filters', 'speechkit'),
            'value' => empty($registered) ? __('None', 'speechkit') : implode(', ', $registered),
            'debug' => empty($registered) ? 'none' : implode(', ', $registered),
        ];
    }

    /**
     * Add notice settings to the info debugging array.
     *
     * @since 5.4.0
     * @since 6.0.0 Make static.
     *
     * @param array $info Debugging info array
     */
    public static function addNoticeSettings(array &$info): void
    {
        $info['beyondwords']['fields']['beyondwords_date_activated'] = [
            'label' => __('Date Activated', 'speechkit'),
            'value' => get_option('beyondwords_date_activated', ''),
        ];

        $info['beyondwords']['fields']['beyondwords_notice_review_dismissed'] = [
            'label' => __('Review Notice Dismissed', 'speechkit'),
            'value' => get_option('beyondwords_notice_review_dismissed', ''),
        ];
    }

    /**
     * Add a single constant to the debugging info array.
     *
     * @since 3.7.0
     * @since 5.0.0 Handle boolean values.
     * @since 6.0.0 Make static.
     *
     * @param array  $info Debugging info array
     * @param string $name Constant name
     */
    public static function addConstant(array &$info, string $name): void
    {
        $value = __('Undefined', 'speechkit');

        if (defined($name)) {
            $value = constant($name);

            if (is_bool($value)) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
                $value = (true === $value) ? 'True' : 'False';
            }
        }

        $info['beyondwords']['fields'][$name] = [
            'label' => $name,
            'value' => $value,
            'debug' => $value,
        ];
    }

    /**
     * Mask a sensitive string for display in Site Health.
     *
     * @since  3.7.0
     * @static
     *
     * @param string $string
     *
     */
    public static function maskString(string|false $string, int $count = 4, string $char = 'X'): string
    {
        if (! is_string($string)) {
            return '';
        }

        if (strlen($string) < 8) {
            return str_repeat($char, strlen($string));
        } else {
            return str_repeat($char, strlen($string) - $count) . substr($string, (-$count));
        }
    }
}
