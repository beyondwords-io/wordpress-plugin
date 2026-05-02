<?php

declare(strict_types=1);

/**
 * BeyondWords Component: Select Voice
 *
 * @package BeyondWords
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Post;

/**
 * SelectVoice
 *
 * @since 4.0.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined('ABSPATH') || exit;

class SelectVoice
{
    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function init()
    {
        add_action('rest_api_init', [self::class, 'rest_api_init_callback']);
        add_action('admin_enqueue_scripts', [self::class, 'admin_enqueue_scripts_callback']);

        add_action('wp_loaded', function (): void {
            $post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

            if (is_array($post_types)) {
                foreach ($post_types as $post_type) {
                    add_action("save_post_{$post_type}", [self::class, 'save'], 10);
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
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param \WP_Post $post The post object.
     *
     * @return string|null
     */
    public static function element($post)
    {
        $language_code = self::get_language_code($post->ID);
        $voice_id = self::get_voice_id($post->ID);
        $languages = \BeyondWords\Core\ApiClient::get_languages();
        $voices = self::get_voices_for_language($language_code);

        wp_nonce_field('beyondwords_select_voice', 'beyondwords_select_voice_nonce');

        self::render_language_select($languages, $language_code);
        self::render_voice_select($voices, $voice_id, $language_code);
        self::render_loading_spinner();
    }

    /**
     * Get the language code for a post.
     *
     * @since 6.0.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param int $post_id The post ID.
     * @return string|false The language code or false if not set.
     */
    private static function get_language_code(int $post_id)
    {
        $post_language_code = get_post_meta($post_id, 'beyondwords_language_code', true);
        return $post_language_code ?: '';
    }

    /**
     * Get the voice ID for a post.
     *
     * @since 6.0.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param int $post_id The post ID.
     * @return string|false The voice ID or false if not set.
     */
    private static function get_voice_id(int $post_id)
    {
        $post_voice_id = get_post_meta($post_id, 'beyondwords_body_voice_id', true);
        return $post_voice_id ?: '';
    }

    /**
     * Get voices for a language code.
     *
     * @since 6.0.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param string|false $language_code The language code.
     * @return array The voices array.
     */
    private static function get_voices_for_language($language_code): array
    {
        if ($language_code === false || $language_code === '') {
            return [];
        }

        $voices = \BeyondWords\Core\ApiClient::get_voices($language_code);
        return is_array($voices) ? $voices : [];
    }

    /**
     * Render the language select dropdown.
     *
     * @since 6.0.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param array $languages The languages array.
     * @param string|false $selected_lang_code The selected language code.
     */
    private static function render_language_select(array $languages, $selected_lang_code): void
    {
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
                if (empty($language['code']) || empty($language['name']) || empty($language['accent'])) {
                    continue;
                }
                printf(
                    '<option value="%s" data-default-voice-id="%s" %s>%s (%s)</option>',
                    esc_attr($language['code']),
                    esc_attr($language['default_voices']['body']['id'] ?? ''),
                    selected(strval($language['code']), strval($selected_lang_code)),
                    esc_html($language['name']),
                    esc_html($language['accent'])
                );
            }
            ?>
        </select>
        <?php
    }

    /**
     * Render the voice select dropdown.
     *
     * @since 6.0.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param array $voices The voices array.
     * @param string|false $selected_voice_id The selected voice ID.
     * @param string|false $language_code The language code.
     */
    private static function render_voice_select(array $voices, $selected_voice_id, $language_code): void
    {
        ?>
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
            <?php echo disabled(!strval($language_code)) ?>
        >
            <?php
            foreach ($voices as $voice) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($voice['id']),
                    selected(strval($voice['id']), strval($selected_voice_id)),
                    esc_html($voice['name'])
                );
            }
            ?>
        </select>
        <?php
    }

    /**
     * Render the loading spinner.
     *
     * @since 6.0.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    private static function render_loading_spinner(): void
    {
        ?>
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
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public static function save($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! isset($_POST['beyondwords_language_code']) ||
            ! isset($_POST['beyondwords_voice_id']) ||
            ! isset($_POST['beyondwords_select_voice_nonce'])
        ) {
            return $post_id;
        }

        // "save_post" can be triggered at other times, so verify this request came from the our component
        if (
            ! wp_verify_nonce(
                sanitize_key($_POST['beyondwords_select_voice_nonce']),
                'beyondwords_select_voice'
            )
        ) {
            return $post_id;
        }

        $language_code = sanitize_text_field(wp_unslash($_POST['beyondwords_language_code']));

        if (! empty($language_code)) {
            update_post_meta($post_id, 'beyondwords_language_code', $language_code);
        } else {
            delete_post_meta($post_id, 'beyondwords_language_code');
        }

        $voice_id = sanitize_text_field(wp_unslash($_POST['beyondwords_voice_id']));

        if (! empty($voice_id)) {
            update_post_meta($post_id, 'beyondwords_body_voice_id', $voice_id);
            update_post_meta($post_id, 'beyondwords_title_voice_id', $voice_id);
            update_post_meta($post_id, 'beyondwords_summary_voice_id', $voice_id);
        } else {
            delete_post_meta($post_id, 'beyondwords_body_voice_id');
            delete_post_meta($post_id, 'beyondwords_title_voice_id');
            delete_post_meta($post_id, 'beyondwords_summary_voice_id');
        }

        return $post_id;
    }

    /**
     * Register WP REST API route
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @return void
     */
    public static function rest_api_init_callback()
    {
        // Languages endpoint
        register_rest_route('beyondwords/v1', '/languages', [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'languages_rest_api_response'],
            'permission_callback' => fn() => current_user_can('edit_posts'),
        ]);

        // Voices endpoint
        register_rest_route('beyondwords/v1', '/languages/(?P<languageCode>[a-zA-Z0-9-_]+)/voices', [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'voices_rest_api_response'],
            'permission_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }

    /**
     * "Languages" WP REST API response (required for the Gutenberg editor).
     *
     * @since 4.0.0
     * @since 5.4.0 No longer filter by "Languages" plugin setting.
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @return \WP_REST_Response
     */
    public static function languages_rest_api_response()
    {
        $languages = \BeyondWords\Core\ApiClient::get_languages();

        return new \WP_REST_Response($languages);
    }

    /**
     * "Voices" WP REST API response (required for the Gutenberg editor
     * and Block Editor).
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @return \WP_REST_Response
     */
    public static function voices_rest_api_response(\WP_REST_Request $data)
    {
        $params = $data->get_url_params();

        $voices = \BeyondWords\Core\ApiClient::get_voices($params['languageCode']);

        return new \WP_REST_Response($voices);
    }

    /**
     * Register the component scripts.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @param string $hook Page hook
     *
     * @return void
     */
    public static function admin_enqueue_scripts_callback($hook)
    {
        if (! \BeyondWords\Core\CoreUtils::is_gutenberg_page() && ( $hook === 'post.php' || $hook === 'post-new.php')) {
            wp_register_script(
                'beyondwords-metabox--select-voice',
                BEYONDWORDS__PLUGIN_URI . 'src/post/select-voice/classic-metabox.js',
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
                [
                    'nonce' => wp_create_nonce('wp_rest'),
                    'root' => esc_url_raw(rest_url()),
                ]
            );

            wp_enqueue_script('beyondwords-metabox--select-voice');
        }
    }
}
