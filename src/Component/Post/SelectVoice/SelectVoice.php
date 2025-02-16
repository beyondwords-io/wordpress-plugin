<?php

declare(strict_types=1);

/**
 * BeyondWords Component: Select Voice
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.0.0
 */

namespace Beyondwords\Wordpress\Component\Post\SelectVoice;

use Beyondwords\Wordpress\Core\CoreUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\Languages\Languages;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Core\ApiClient;

/**
 * SelectVoice
 *
 * @since 4.0.0
 */
class SelectVoice
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('rest_api_init', array($this, 'restApiInit'));
        add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));

        add_action('wp_loaded', function () {
            $postTypes = SettingsUtils::getCompatiblePostTypes();

            if (is_array($postTypes)) {
                foreach ($postTypes as $postType) {
                    add_action("save_post_{$postType}", array($this, 'save'), 10);
                }
            }
        });
    }

    /**
     * HTML output for this component.
     *
     * @since 4.0.0
     * @since 4.5.1 Hide element if no language data exists.
     *
     * @param WP_Post $post The post object.
     *
     * @return string|null
     */
    public function element($post)
    {
        if (! get_option('beyondwords_languages')) {
            return;
        }

        $languages         = $this->getFilteredLanguages();
        $currentLanguageId = get_post_meta($post->ID, 'beyondwords_language_id', true);

        $voices         = ApiClient::getVoices($currentLanguageId);
        $currentVoiceId = get_post_meta($post->ID, 'beyondwords_body_voice_id', true);

        if (! is_array($voices)) {
            $voices = [];
        }

        wp_nonce_field('beyondwords_select_voice', 'beyondwords_select_voice_nonce');
        ?>
        <p
            id="beyondwords-metabox-select-voice--language-id"
            class="post-attributes-label-wrapper page-template-label-wrapper"
        >
            <label class="post-attributes-label" for="beyondwords_language_id">
                Language
            </label>
        </p>
        <select id="beyondwords_language_id" name="beyondwords_language_id" style="width: 100%;">
            <option value="">Project default</option>
            <?php
            foreach ($languages as $language) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($language['id']),
                    selected(strval($language['id']), $currentLanguageId),
                    esc_html($language['name'])
                );
            }
            ?>
        </select>
        <p
            id="beyondwords-metabox-select-voice--voice-id"
            class="post-attributes-label-wrapper page-template-label-wrapper"
        >
            <label class="post-attributes-label" for="beyondwords_voice_id">
                Voice
            </label>
        </p>
        <select
            id="beyondwords_voice_id"
            name="beyondwords_voice_id"
            style="width: 100%;"
            <?php echo disabled(!strval($currentLanguageId)) ?>
        >
            <option value=""></option>
            <?php
            foreach ($voices as $voice) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($voice['id']),
                    selected(strval($voice['id']), $currentVoiceId),
                    esc_html($voice['name'])
                );
            }
            ?>
        </select>
        <?php
    }

    /**
     * Save the meta when the post is saved.
     *
     * @since 4.0.0
     *
     * @param int $postId The ID of the post being saved.
     */
    public function save($postId)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $postId;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! isset($_POST['beyondwords_language_id']) ||
            ! isset($_POST['beyondwords_voice_id']) ||
            ! isset($_POST['beyondwords_select_voice_nonce'])
        ) {
            return $postId;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_select_voice_nonce']),
                'beyondwords_select_voice'
            )
        ) {
            return $postId;
        }

        $languageId = sanitize_text_field(wp_unslash($_POST['beyondwords_language_id']));

        if (! empty($languageId)) {
            update_post_meta($postId, 'beyondwords_language_id', $languageId);
        } else {
            delete_post_meta($postId, 'beyondwords_language_id');
        }

        $voiceId = sanitize_text_field(wp_unslash($_POST['beyondwords_voice_id']));

        if (! empty($voiceId)) {
            update_post_meta($postId, 'beyondwords_body_voice_id', $voiceId);
            update_post_meta($postId, 'beyondwords_title_voice_id', $voiceId);
            update_post_meta($postId, 'beyondwords_summary_voice_id', $voiceId);
        } else {
            delete_post_meta($postId, 'beyondwords_body_voice_id');
            delete_post_meta($postId, 'beyondwords_title_voice_id');
            delete_post_meta($postId, 'beyondwords_summary_voice_id');
        }

        return $postId;
    }

    /**
     * Register WP REST API route
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function restApiInit()
    {
        // Languages endpoint
        register_rest_route('beyondwords/v1', '/languages', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'languagesRestApiResponse'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));

        // Voices endpoint
        register_rest_route('beyondwords/v1', '/languages/(?P<languageId>[0-9]+)/voices', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'voicesRestApiResponse'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));
    }

    /**
     * Get languages from BeyondWords API and filter by "Languages" plugin setting.
     *
     * @since 4.0.0
     * @since 4.5.1 Exit early with an empty array if language API call fails.
     *
     * @return array Array of languages
     */
    public function getFilteredLanguages()
    {
        $languages = ApiClient::getLanguages();

        if (! is_array($languages)) {
            return [];
        }

        $languagesSetting = get_option('beyondwords_languages', Languages::DEFAULT_LANGUAGES);

        // Filter languages according to "Languages" plugin setting
        if (is_array($languages) && is_array($languagesSetting)) {
            $languages = array_values(array_filter($languages, function ($language) {
                return $this->languageIsInSettings($language);
            }));
        }

        return $languages;
    }

    /**
     * Get languages from BeyondWords API and filter by "Languages" plugin setting.
     *
     * @since 4.0.0
     *
     * @return array Array of languages
     */
    public function languageIsInSettings($language)
    {
        if (! is_array($language)) {
            return false;
        }

        if (! array_key_exists('id', $language)) {
            return false;
        }

        $languagesSetting = get_option('beyondwords_languages', Languages::DEFAULT_LANGUAGES);

        if (! is_array($languagesSetting)) {
            return false;
        }

        if (! in_array(strval($language['id']), $languagesSetting)) {
            return false;
        }

        return true;
    }

    /**
     * "Languages" WP REST API response (required for the Gutenberg editor).
     *
     * @since 4.0.0
     *
     * @return \WP_REST_Response
     */
    public function languagesRestApiResponse()
    {
        $languages = $this->getFilteredLanguages();

        return new \WP_REST_Response($languages);
    }

    /**
     * "Voices" WP REST API response (required for the Gutenberg editor
     * and Block Editor).
     *
     * @since 4.0.0
     *
     * @return \WP_REST_Response
     */
    public function voicesRestApiResponse(\WP_REST_Request $data)
    {
        $params = $data->get_url_params();

        $voices = ApiClient::getVoices($params['languageId']);

        return new \WP_REST_Response($voices);
    }


    /**
     * Register the component scripts.
     *
     * @since  4.0.0
     *
     * @param string $hook Page hook
     *
     * @return void
     */
    public function adminEnqueueScripts($hook)
    {
        if (! CoreUtils::isGutenbergPage() && ( $hook === 'post.php' || $hook === 'post-new.php')) {
            wp_register_script(
                'beyondwords-metabox--select-voice',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/SelectVoice/classic-metabox.js',
                ['jquery', 'underscore'],
                BEYONDWORDS__PLUGIN_VERSION,
                true
            );

            /**
             * Localize the script to handle ajax requests
             */
            wp_localize_script(
                'beyondwords-metabox--select-voice',
                'beyondwordsData',
                array(
                    'nonce' => wp_create_nonce('wp_rest'),
                    'root' => esc_url_raw(rest_url()),
                )
            );

            wp_enqueue_script('beyondwords-metabox--select-voice');
        }
    }
}
