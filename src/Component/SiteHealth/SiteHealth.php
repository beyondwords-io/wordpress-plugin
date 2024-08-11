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

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Core\Environment;

/**
 * BeyondWords SiteHealth.
 *
 * @since 3.7.0
 */
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
     */
    public function init()
    {
        add_filter('debug_information', array($this, 'debugInformation'));
    }

    /**
     * Add "Site Health" navigation tab.
     *
     * @since  3.7.0
     *
     * @param array $info
     *
     * @return array
     */
    public function debugInformation($info)
    {
        $info['beyondwords']['label'] = __('BeyondWords - Text-to-Speech', 'speechkit');

        $this->addPluginVersion($info);
        $this->addRestApiConnection($info);

        $info['beyondwords']['fields']['compatible-post-types'] = [
            'label' => __('Compatible post types', 'speechkit'),
            'value' => implode(', ', SettingsUtils::getCompatiblePostTypes()),
        ];

        $info['beyondwords']['fields']['incompatible-post-types'] = [
            'label' => __('Incompatible post types', 'speechkit'),
            'value' => implode(', ', SettingsUtils::getIncompatiblePostTypes()),
        ];

        $info['beyondwords']['fields']['beyondwords_api_key'] = [
            'label' => __('API Key', 'speechkit'),
            'value' => SiteHealth::maskString(get_option('beyondwords_api_key')),
        ];

        $info['beyondwords']['fields']['beyondwords_project_id'] = [
            'label' => __('Project ID', 'speechkit'),
            'value' => get_option('beyondwords_project_id'),
        ];

        $this->addContentSettings($info);
        $this->addProjectSettings($info);
        $this->addPlayerSettings($info);

        $info['beyondwords']['fields']['beyondwords_languages'] = [
            'label' => __('Multiple languages', 'speechkit'),
            'value' => (string) wp_json_encode(get_option('beyondwords_languages'), JSON_PRETTY_PRINT),
        ];

        $info['beyondwords']['fields']['beyondwords_settings_updated'] = [
            'label' => __('Settings updated', 'speechkit'),
            'value' => get_option('beyondwords_settings_updated'),
        ];

        $this->addFilters($info);

        $this->addConstant($info, 'BEYONDWORDS_AUTOREGENERATE');

        return $info;
    }

    /**
     * Add plugin version to the info debugging array.
     *
     * @since 4.8.0
     * @static
     *
     * @param array $info Debugging info array
     *
     * @return array
     */
    public function addContentSettings(&$info)
    {
        $info['beyondwords']['fields']['beyondwords_include_title'] = [
            'label' => __('Include title in audio', 'speechkit'),
            'value' => get_option('beyondwords_include_title') ? __('Yes') : __('No'),
            'debug' => get_option('beyondwords_include_title') ? 'yes' : 'no',
        ];

        $info['beyondwords']['fields']['beyondwords_prepend_excerpt'] = [
            'label' => __('Include excerpts in audio', 'speechkit'),
            'value' => get_option('beyondwords_prepend_excerpt') ? __('Yes') : __('No'),
            'debug' => get_option('beyondwords_prepend_excerpt') ? 'yes' : 'no',
        ];

        $info['beyondwords']['fields']['beyondwords_preselect'] = [
            'label' => __('Preselect ‘Generate audio’', 'speechkit'),
            'value' => (string) wp_json_encode(get_option('beyondwords_preselect'), JSON_PRETTY_PRINT),
        ];
    }

    /**
     * Add plugin version to the info debugging array.
     *
     * @since 4.8.0
     * @static
     *
     * @param array $info Debugging info array
     *
     * @return array
     */
    public function addProjectSettings(&$info)
    {
        $info['beyondwords']['fields']['beyondwords_project_language_code'] = [
            'label' => __('Default language code', 'speechkit'),
            'value' => get_option('beyondwords_project_language_code'),
        ];

        $info['beyondwords']['fields']['beyondwords_project_language_id'] = [
            'label' => __('Default language ID', 'speechkit'),
            'value' => get_option('beyondwords_project_language_id'),
        ];

        $info['beyondwords']['fields']['beyondwords_project_title_voice_id'] = [
            'label' => __('Title voice ID', 'speechkit'),
            'value' => get_option('beyondwords_project_title_voice_id'),
        ];

        $info['beyondwords']['fields']['beyondwords_title_voice_speaking_rate'] = [
            'label' => __('Title speaking rate', 'speechkit'),
            'value' => get_option('beyondwords_title_voice_speaking_rate'),
        ];

        $info['beyondwords']['fields']['beyondwords_project_body_voice_id'] = [
            'label' => __('Body voice ID', 'speechkit'),
            'value' => get_option('beyondwords_project_body_voice_id'),
        ];

        $info['beyondwords']['fields']['beyondwords_body_voice_speaking_rate'] = [
            'label' => __('Body speaking rate', 'speechkit'),
            'value' => get_option('beyondwords_body_voice_speaking_rate'),
        ];
    }

    /**
     * Add plugin version to the info debugging array.
     *
     * @since 4.8.0
     * @static
     *
     * @param array $info Debugging info array
     *
     * @return array
     */
    public function addPlayerSettings(&$info)
    {
        $info['beyondwords']['fields']['beyondwords_player_ui'] = [
            'label' => __('Player UI', 'speechkit'),
            'value' => get_option('beyondwords_player_ui'),
        ];

        $info['beyondwords']['fields']['beyondwords_player_style'] = [
            'label' => __('Player style', 'speechkit'),
            'value' => get_option('beyondwords_player_style'),
        ];

        $info['beyondwords']['fields']['beyondwords_player_theme'] = [
            'label' => __('Player theme', 'speechkit'),
            'value' => get_option('beyondwords_player_theme'),
        ];

        $info['beyondwords']['fields']['beyondwords_player_light_theme'] = [
            'label' => __('Light theme', 'speechkit'),
            'value' => (string) wp_json_encode(get_option('beyondwords_player_light_theme'), JSON_PRETTY_PRINT),
        ];

        $info['beyondwords']['fields']['beyondwords_player_dark_theme'] = [
            'label' => __('Dark theme', 'speechkit'),
            'value' => (string) wp_json_encode(get_option('beyondwords_player_dark_theme'), JSON_PRETTY_PRINT),
        ];

        $info['beyondwords']['fields']['beyondwords_player_video_theme'] = [
            'label' => __('Video theme', 'speechkit'),
            'value' => (string) wp_json_encode(get_option('beyondwords_player_video_theme'), JSON_PRETTY_PRINT),
        ];

        $info['beyondwords']['fields']['beyondwords_player_call_to_action'] = [
            'label' => __('Call-to-action', 'speechkit'),
            'value' => get_option('beyondwords_player_call_to_action'),
        ];

        $info['beyondwords']['fields']['beyondwords_player_highlight_sections'] = [
            'label' => __('Text highlighting', 'speechkit'),
            'value' => get_option('beyondwords_player_highlight_sections'),
        ];

        $info['beyondwords']['fields']['beyondwords_player_clickable_sections'] = [
            'label' => __('Playback from segments', 'speechkit'),
            'value' => get_option('beyondwords_player_clickable_sections'),
        ];

        $info['beyondwords']['fields']['beyondwords_player_widget_style'] = [
            'label' => __('Widget style', 'speechkit'),
            'value' => get_option('beyondwords_player_widget_style'),
        ];

        $info['beyondwords']['fields']['beyondwords_player_widget_position'] = [
            'label' => __('Widget position', 'speechkit'),
            'value' => get_option('beyondwords_player_widget_position'),
        ];

        $info['beyondwords']['fields']['beyondwords_player_skip_button_style'] = [
            'label' => __('Skip button style', 'speechkit'),
            'value' => get_option('beyondwords_player_skip_button_style'),
        ];
    }

    /**
     * Add plugin version to the info debugging array.
     *
     * @since  3.7.0
     * @static
     *
     * @param array  $info Debugging info array
     *
     * @return array
     */
    public function addPluginVersion(&$info)
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
     * @since  3.7.0
     * @static
     *
     * @param array  $info Debugging info array
     *
     * @return array
     */
    public function addRestApiConnection(&$info)
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
            'sslverify'   => true,
        ]);

        if (! is_wp_error($response)) {
            $info['beyondwords']['fields']['api-communication'] = array(
                'label' => __('Communication with REST API'),
                'value' => __('BeyondWords API is reachable'),
                'debug' => 'true',
            );
        } else {
            $info['beyondwords']['fields']['api-communication'] = array(
                'label' => __('Communication with REST API'),
                'value' => sprintf(
                    /* translators: 1: The IP address the REST API resolves to. 2: The error returned by the lookup. */
                    __('Unable to reach BeyondWords API at %1$s: %2$s'),
                    gethostbyname(Environment::getApiUrl()),
                    $response->get_error_message()
                ),
                'debug' => $response->get_error_message(),
            );
        }
    }

    /**
     * Adds debugging data for the BeyondWords REST API connection.
     *
     * @since 4.8.0
     *
     * @param array $info Debugging info array
     *
     * @return array
     */
    public function addFilters(&$info)
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
     * Add a constant to the debugging info array.
     *
     * @since  3.7.0
     * @static
     *
     * @param array  $info Debugging info array
     * @param string $name Constant name
     *
     * @return array
     */
    public function addConstant(&$info, $name)
    {
        $info['beyondwords']['fields'][$name] = [
            'label' => $name,
            'value' => ( defined($name) ? constant($name) : __('Undefined') ),
            'debug' => ( defined($name) ? constant($name) : 'undefined' ),
        ];
    }

    /**
     * Mask a sensitive string for display in Site Health.
     *
     * @since  3.7.0
     * @static
     *
     * @param string $string
     * @param int $count
     * @param string $char
     *
     * @return string
     */
    public static function maskString($string, $count = 4, $char = 'X')
    {
        if (! is_string($string)) {
            return '';
        }

        if (strlen($string) < 8) {
            return str_repeat($char, strlen($string));
        } else {
            return str_repeat($char, strlen($string) - $count) . substr($string, (0 - $count));
        }
    }
}
