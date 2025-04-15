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
     * @since 5.4.0 Always display all languages and associated voices.
     *
     * @param WP_Post $post The post object.
     *
     * @return string|null
     */
    public function element($post)
    {
        $postLanguageCode = get_post_meta($post->ID, 'beyondwords_language_code', true);
        $postVoiceId      = get_post_meta($post->ID, 'beyondwords_body_voice_id', true);

        $languageCode = $postLanguageCode ?: get_option('beyondwords_project_language_code');
        $voiceId      = $postVoiceId ?: get_option('beyondwords_project_body_voice_id');

        $languages = ApiClient::getLanguages();
        $voices    = ApiClient::getVoices($languageCode);

        if (! is_array($voices)) {
            $voices = [];
        }

        wp_nonce_field('beyondwords_select_voice', 'beyondwords_select_voice_nonce');
        ?>
        <p
            id="beyondwords-metabox-select-voice--language-code"
            class="post-attributes-label-wrapper page-template-label-wrapper"
        >
            <label class="post-attributes-label" for="beyondwords_language_code">
                Language
            </label>
        </p>
        <select id="beyondwords_language_code" name="beyondwords_language_code" style="width: 100%;">
            <?php
            foreach ($languages as $language) {
                if (empty($language['code']) || empty($language['name'])  || empty($language['accent'])) {
                    continue;
                }
                printf(
                    '<option value="%s" data-default-voice-id="%s" %s>%s (%s)</option>',
                    esc_attr($language['code']),
                    esc_attr($language['default_voices']['body']['id'] ?? ''),
                    selected(strval($language['code']), strval($languageCode)),
                    esc_html($language['name']),
                    esc_html($language['accent'])
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
            <?php echo disabled(!strval($languageCode)) ?>
        >
            <?php
            foreach ($voices as $voice) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($voice['id']),
                    selected(strval($voice['id']), strval($voiceId)),
                    esc_html($voice['name'])
                );
            }
            ?>
        </select>
        <img
            src="/wp-admin/images/spinner.gif"
            class="beyondwords-settings__loader"
            style="display:none; padding: 3px 0;"
        />
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
            ! isset($_POST['beyondwords_language_code']) ||
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

        $languageCode = sanitize_text_field(wp_unslash($_POST['beyondwords_language_code']));

        if (! empty($languageCode)) {
            update_post_meta($postId, 'beyondwords_language_code', $languageCode);
        } else {
            delete_post_meta($postId, 'beyondwords_language_code');
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
        register_rest_route('beyondwords/v1', '/languages/(?P<languageCode>[a-zA-Z0-9-_]+)/voices', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'voicesRestApiResponse'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));
    }

    /**
     * "Languages" WP REST API response (required for the Gutenberg editor).
     *
     * @since 4.0.0
     * @since 5.4.0 No longer filter by "Languages" plugin setting.
     *
     * @return \WP_REST_Response
     */
    public function languagesRestApiResponse()
    {
        $languages = ApiClient::getLanguages();

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

        $voices = ApiClient::getVoices($params['languageCode']);

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
