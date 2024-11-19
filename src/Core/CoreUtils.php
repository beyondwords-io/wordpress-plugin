<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core;

/**
 * BeyondWords Core Utilities.
 *
 * @package    Beyondwords
 * @subpackage Beyondwords/includes
 * @author     Stuart McAlpine <stu@beyondwords.io>
 * @since      3.5.0
 */
class CoreUtils
{
    /**
     * Check to see if the Gutenberg Editor is being used.
     *
     * @link https://wordpress.stackexchange.com/a/324866
     *
     * @since 3.0.0
     * @since 3.5.0 Moved from Core\Utils to Core\CoreUtils
     */
    public static function isGutenbergPage()
    {
        if (function_exists('is_gutenberg_page') && is_gutenberg_page()) {
            // The Gutenberg plugin is on.
            return true;
        }

        $currentScreen = null;

        if (function_exists('get_current_screen')) {
            $currentScreen = get_current_screen();
        }

        if ($currentScreen === null) {
            return false;
        }

        if (method_exists($currentScreen, 'is_block_editor') && $currentScreen->is_block_editor()) {
            // Gutenberg page on 5+.
            return true;
        }

        return false;
    }

    /**
     * Check to see if current screen is an edit screen
     * (this includes the screen that lists the posts).
     *
     * @since 4.0.0
     * @since 4.0.5 Ensure is_admin() and $screen
     */
    public static function isEditScreen()
    {
        if (! is_admin()) {
            return false;
        }

        if (! function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        if (! $screen || ! ($screen instanceof \WP_Screen)) {
            return false;
        }

        if ($screen->parent_base === 'edit' || $screen->base === 'post') {
            return true;
        }

        return false;
    }

    /**
     * Get the BeyondWords post meta keys.
     *
     * @since 4.1.0
     *
     * @param string $type Type (current|deprecated|all).
     *
     * @throws Exception
     *
     * @return string[] Post meta keys.
     **/
    public static function getPostMetaKeys($type = 'current')
    {
        $current = [
            'beyondwords_generate_audio',
            'beyondwords_project_id',
            'beyondwords_content_id',
            'beyondwords_preview_token',
            'beyondwords_player_style',
            'beyondwords_language_id',
            'beyondwords_title_voice_id',
            'beyondwords_body_voice_id',
            'beyondwords_summary_voice_id',
            'beyondwords_error_message',
            'beyondwords_disabled',
            'beyondwords_delete_content',
        ];

        $deprecated = [
            'beyondwords_podcast_id',
            'beyondwords_hash',
            'publish_post_to_speechkit',
            'speechkit_hash',
            'speechkit_generate_audio',
            'speechkit_project_id',
            'speechkit_podcast_id',
            'speechkit_error_message',
            'speechkit_disabled',
            'speechkit_access_key',
            'speechkit_error',
            'speechkit_info',
            'speechkit_response',
            'speechkit_retries',
            'speechkit_status',
            'speechkit_updated_at',
            '_speechkit_link',
            '_speechkit_text',
        ];

        $keys = [];

        switch ($type) {
            case 'current':
                $keys = $current;
                break;
            case 'deprecated':
                $keys = $deprecated;
                break;
            case 'all':
                $keys = array_merge($current, $deprecated);
                break;
            default:
                throw \Exception('Unexpected $type param for CoreUtils::getPostMetaKeys()');
                break;
        }

        return $keys;
    }

    /**
     * Get the BeyondWords post meta keys.
     *
     * @since 4.1.0
     *
     * @param string $type Type (current|deprecated|all).
     *
     * @throws Exception
     *
     * @return string[] Post meta keys.
     **/
    public static function getOptions($type = 'current')
    {
        $current = [
            // v5.x
            'beyondwords_date_activated',
            'beyondwords_notice_review_dismissed',
            'beyondwords_player_call_to_action',
            'beyondwords_player_clickable_sections',
            'beyondwords_player_highlight_sections',
            'beyondwords_player_skip_button_style',
            'beyondwords_player_theme',
            'beyondwords_player_theme_dark',
            'beyondwords_player_theme_light',
            'beyondwords_player_theme_video',
            'beyondwords_player_widget_position',
            'beyondwords_player_widget_style',
            'beyondwords_project_auto_publish_enabled',
            'beyondwords_project_body_voice_id',
            'beyondwords_project_body_voice_speaking_rate',
            'beyondwords_project_language_code',
            'beyondwords_project_language_id',
            'beyondwords_project_title_enabled',
            'beyondwords_project_title_voice_id',
            'beyondwords_project_title_voice_speaking_rate',
            'beyondwords_video_enabled',
            // v4.x
            'beyondwords_languages',
            'beyondwords_player_ui',
            'beyondwords_player_style',
            'beyondwords_player_version',
            'beyondwords_settings_updated',
            'beyondwords_valid_api_connection',
            // v3.7.0 beyondwords_*
            'beyondwords_api_key',
            'beyondwords_prepend_excerpt',
            'beyondwords_preselect',
            'beyondwords_project_id',
            'beyondwords_version',
        ];

        $deprecated = [
            // v3.0.0 speechkit_*
            'speechkit_api_key',
            'speechkit_prepend_excerpt',
            'speechkit_preselect',
            'speechkit_project_id',
            'speechkit_version',
            // deprecated < v3.0
            'speechkit_settings',
            'speechkit_enable',
            'speechkit_id',
            'speechkit_select_post_types',
            'speechkit_selected_categories',
            'speechkit_enable_telemetry',
            'speechkit_rollbar_access_token',
            'speechkit_rollbar_error_notice',
            'speechkit_merge_excerpt',
            'speechkit_enable_marfeel_comp',
            'speechkit_wordpress_cron',
        ];

        $keys = [];

        switch ($type) {
            case 'current':
                $keys = $current;
                break;
            case 'deprecated':
                $keys = $deprecated;
                break;
            case 'all':
                $keys = array_merge($current, $deprecated);
                break;
            default:
                throw \Exception('Unexpected $type param for CoreUtils::getOptions()');
                break;
        }

        return $keys;
    }
}
