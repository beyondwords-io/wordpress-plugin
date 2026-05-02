<?php

declare(strict_types=1);

namespace BeyondWords\Post;

/**
 * BeyondWords Post Content Utilities.
 *
 * @package    Beyondwords
 * @subpackage Beyondwords/includes
 * @author     Stuart McAlpine <stu@beyondwords.io>
 * @since      3.5.0
 */
defined('ABSPATH') || exit;

class PostContentUtils
{
    public const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * Get the content "body" param for the audio, ready to be sent to the
     * BeyondWords API.
     *
     * From API version 1.1 the "summary" param is going to be used differently,
     * so for WordPress we now prepend the WordPress excerpt to the "body" param.
     *
     * @param int|\WP_Post $post The WordPress post ID, or post object.
     *
     * @since 4.6.0
     *
     * @return string The content body param.
     */
    public static function get_content_body(int|\WP_Post $post): string|null
    {
        $post = get_post($post);

        if (!($post instanceof \WP_Post)) {
            throw new \Exception(esc_html__('Post Not Found', 'speechkit'));
        }

        $summary = PostContentUtils::get_post_summary($post);
        $body    = PostContentUtils::get_post_body($post);

        if ($summary) {
            $format = PostContentUtils::get_post_summary_wrapper_format($post);

            $body = sprintf($format, $summary) . $body;
        }

        return $body;
    }

    /**
     * Get the post body for the audio content.
     *
     * @since 3.0.0
     * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
     * @since 3.8.0 Exclude Gutenberg blocks with attribute { beyondwordsAudio: false }
     * @since 4.0.0 Renamed from PostContentUtils::getSourceTextForAudio() to PostContentUtils::getBody()
     * @since 4.6.0 Renamed from PostContentUtils::getBody() to PostContentUtils::get_post_body()
     * @since 4.7.0 Remove wpautop filter for block editor API requests.
     * @since 5.0.0 Remove SpeechKit-Start shortcode.
     * @since 5.0.0 Remove beyondwords_content filter.
     *
     * @param int|\WP_Post $post The WordPress post ID, or post object.
     *
     * @return string The body (the processed $post->post_content).
     */
    public static function get_post_body(int|\WP_Post $post): string|null
    {
        $post = get_post($post);

        if (!($post instanceof \WP_Post)) {
            throw new \Exception(esc_html__('Post Not Found', 'speechkit'));
        }

        $content = PostContentUtils::get_content_without_excluded_blocks($post);

        if (has_blocks($post)) {
            // wpautop breaks our HTML markup when block editor paragraphs are empty
            remove_filter('the_content', 'wpautop');

            // But we still want to remove empty lines
            $content = preg_replace('/^\h*\v+/m', '', $content);
        }

        // Apply the_content filters to handle shortcodes etc
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Applying core WordPress filter
        $content = apply_filters('the_content', $content);

        // Trim to remove trailing newlines – common for WordPress content
        return trim($content);
    }

    /**
     * Get the post summary wrapper format.
     *
     * This is a <div> with optional attributes depending on the BeyondWords
     * data of the post.
     *
     * @param int|\WP_Post $post The WordPress post ID, or post object.
     *
     * @since 4.6.0
     *
     * @return string The summary wrapper <div>.
     */
    public static function get_post_summaryWrapperFormat(int|\WP_Post $post): string
    {
        $post = get_post($post);

        if (!($post instanceof \WP_Post)) {
            throw new \Exception(esc_html__('Post Not Found', 'speechkit'));
        }

        $summary_voice_id = intval(get_post_meta($post->ID, 'beyondwords_summary_voice_id', true));

        if ($summary_voice_id > 0) {
            return '<div data-beyondwords-summary="true" data-beyondwords-voice-id="' . $summary_voice_id . '">%s</div>';
        }

        return '<div data-beyondwords-summary="true">%s</div>';
    }

    /**
     * Get the post summary for the audio content.
     *
     * @param int|\WP_Post $post The WordPress post ID, or post object.
     *
     * @since 4.0.0
     * @since 4.6.0 Renamed from PostContentUtils::getSummary() to PostContentUtils::get_post_summary()
     *
     * @return string The summary.
     */
    public static function get_post_summary(int|\WP_Post $post): string|null
    {
        $post = get_post($post);

        if (!($post instanceof \WP_Post)) {
            throw new \Exception(esc_html__('Post Not Found', 'speechkit'));
        }

        $summary = null;

        // Optionally send the excerpt to the REST API, if the plugin setting has been checked
        $prepend_excerpt = get_option('beyondwords_prepend_excerpt');

        if ($prepend_excerpt && has_excerpt($post)) {
            // Escape characters
            $summary = htmlentities($post->post_excerpt, ENT_QUOTES | ENT_XHTML);
            // Apply WordPress filters
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Applying core WordPress filter
            $summary = apply_filters('get_the_excerpt', $summary);
            // Convert line breaks into paragraphs
            $summary = trim(wpautop($summary));
        }

        return $summary;
    }

    /**
     * Get the post content without blocks which have been filtered.
     *
     * We have added buttons into the Gutenberg editor to optionally exclude selected
     * blocks from the source text for audio.
     *
     * This method filters all blocks, removing any which have been excluded.
     *
     * @param int|\WP_Post $post The WordPress post ID, or post object.
     *
     * @since 3.8.0
     * @since 4.0.0 Replace for loop with array_reduce
     * @since 6.0.0 Remove beyondwordsMarker attribute from rendered blocks.
     *
     * @return string The post body without excluded blocks.
     */
    public static function get_content_without_excluded_blocks(int|\WP_Post $post): string
    {
        if (! has_blocks($post)) {
            return trim($post->post_content);
        }

        $blocks = parse_blocks($post->post_content);
        $output = '';

        $blocks = PostContentUtils::get_audio_enabled_blocks($post);

        foreach ($blocks as $block) {
            $output .= render_block($block);
        }

        return $output;
    }

    /**
     * Get audio-enabled blocks.
     *
     * @param int|\WP_Post $post The WordPress post ID, or post object.
     *
     * @since 4.0.0
     * @since 5.0.0 Remove beyondwords_post_audio_enabled_blocks filter.
     *
     * @return array The blocks.
     */
    public static function get_audio_enabled_blocks(int|\WP_Post $post): array
    {
        $post = get_post($post);

        if (! ($post instanceof \WP_Post)) {
            return [];
        }

        if (! has_blocks($post)) {
            return [];
        }

        $all_blocks = parse_blocks($post->post_content);

        return array_filter($all_blocks, function ($block) {
            $enabled = true;

            if (is_array($block['attrs']) && isset($block['attrs']['beyondwordsAudio'])) {
                $enabled = (bool) $block['attrs']['beyondwordsAudio'];
            }

            return $enabled;
        });
    }

    /**
     * Get the body param we pass to the API.
     *
     * @since 3.0.0  Introduced as getBodyJson.
     * @since 3.3.0  Added metadata to aid custom playlist generation.
     * @since 3.5.0  Moved from Core\Utils to Component\Post\PostUtils.
     * @since 3.10.4 Rename `published_at` API param to `publish_date`.
     * @since 4.0.0  Use new API params.
     * @since 4.0.3  Ensure `image_url` is always a string.
     * @since 4.3.0  Rename from getBodyJson to getContentParams.
     * @since 4.6.0  Remove summary param & prepend body with summary.
     * @since 5.0.0  Remove beyondwords_body_params filter.
     * @since 6.0.0  Cast return value to string.
     *
     * @static
     * @param int $post_id WordPress Post ID.
     *
     * @return string JSON endoded params.
     **/
    public static function get_content_params(int $post_id): array|string
    {
        $body = [
            'type'         => 'auto_segment',
            'title'        => get_the_title($post_id),
            'body'         => PostContentUtils::get_content_body($post_id),
            'source_url'   => get_the_permalink($post_id),
            'source_id'    => strval($post_id),
            'author'       => PostContentUtils::get_author_name($post_id),
            'image_url'    => strval(wp_get_original_image_url(get_post_thumbnail_id($post_id))),
            'metadata'     => PostContentUtils::get_metadata($post_id),
            'publish_date' => get_post_time(PostContentUtils::DATE_FORMAT, true, $post_id),
        ];

        $status = get_post_status($post_id);

        /*
         * If the post status is draft/pending then we explicity send
         * { published: false } to the BeyondWords API, to prevent the
         * generated audio from being published in playlists.
         *
         * We also omit { publish_date } because get_post_time() returns `false`
         * for posts which are "Pending Review".
         */
        if (in_array($status, ['draft', 'pending'])) {
            $body['published'] = false;
            unset($body['publish_date']);
        } else {
            /**
             * Filters whether generated content is auto-published to BeyondWords.
             *
             * Replaces the v6.x `beyondwords_project_auto_publish_enabled` setting.
             * Default `true` matches the previous default; sites that need to keep
             * generated content as drafts can return `false`.
             *
             * @since 7.0.0
             *
             * @param bool $auto_publish Whether to mark generated content as published.
             * @param int  $post_id       WordPress post ID.
             */
            if (apply_filters('beyondwords_auto_publish', true, $post_id)) {
                $body['published'] = true;
            }
        }

        $language_code = get_post_meta($post_id, 'beyondwords_language_code', true);

        if ($language_code) {
            $body['language'] = $language_code;
        }

        $body_voice_id = intval(get_post_meta($post_id, 'beyondwords_body_voice_id', true));

        if ($body_voice_id > 0) {
            $body['body_voice_id'] = $body_voice_id;
        }

        $title_voice_id = intval(get_post_meta($post_id, 'beyondwords_title_voice_id', true));

        if ($title_voice_id > 0) {
            $body['title_voice_id'] = $title_voice_id;
        }

        $summary_voice_id = intval(get_post_meta($post_id, 'beyondwords_summary_voice_id', true));

        if ($summary_voice_id > 0) {
            $body['summary_voice_id'] = $summary_voice_id;
        }

        /**
         * Filters the params we send to the BeyondWords API 'content' endpoint.
         *
         * @since 4.0.0 Introduced as beyondwords_body_params
         * @since 4.3.0 Renamed from beyondwords_body_params to beyondwords_content_params
         *
         * @param array $body   The params we send to the BeyondWords API.
         * @param array $post_id WordPress post ID.
         */
        $body = apply_filters('beyondwords_content_params', $body, $post_id);

        return (string) wp_json_encode($body);
    }

    /**
     * Get the post metadata to send with BeyondWords API requests.
     *
     * The metadata key is defined by the BeyondWords API as "A custom object
     * for storing meta information".
     *
     * The metadata values are used to create filters for playlists in the
     * BeyondWords dashboard.
     *
     * We currently only include taxonomies by default, and the output of this
     * method can be filtered using the `beyondwords_post_metadata` filter.
     *
     * @since 3.3.0
     * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils.
     * @since 5.0.0 Remove beyondwords_post_metadata filter.
     *
     * @param int $post_id Post ID.
     *
     * @return object The metadata object (empty if no metadata).
     */
    public static function get_metadata(int $post_id): array|object
    {
        $metadata = new \stdClass();

        $taxonomy = PostContentUtils::get_all_taxonomies_and_terms($post_id);

        if (count((array)$taxonomy)) {
            $metadata->taxonomy = $taxonomy;
        }

        return $metadata;
    }

    /**
     * Get all taxonomies, and their selected terms, for a post.
     *
     * Returns an associative array of taxonomy names and terms.
     *
     * For example:
     *
     * array(
     *     "categories" => array("Category 1"),
     *     "post_tag" => array("Tag 1", "Tag 2", "Tag 3"),
     * )
     *
     * @since 3.3.0
     * @since 3.5.0 Moved from Core\Utils to Component\Post\PostUtils
     *
     * @param int $post_id Post ID.
     *
     * @return object The taxonomies object (empty if no taxonomies).
     */
    public static function get_all_taxonomies_and_terms(int $post_id): array|object
    {
        $post_type = get_post_type($post_id);

        $post_type_taxonomies = get_object_taxonomies($post_type);

        $taxonomies = new \stdClass();

        foreach ($post_type_taxonomies as $post_type_taxonomy) {
            $terms = get_the_terms($post_id, $post_type_taxonomy);

            if (! empty($terms) && ! is_wp_error($terms)) {
                $taxonomies->{(string)$post_type_taxonomy} = wp_list_pluck($terms, 'name');
            }
        }

        return $taxonomies;
    }

    /**
     * Get author name for a post.
     *
     * @since 3.10.4
     *
     * @param int $post_id Post ID.
     */
    public static function get_author_name(int $post_id): string
    {
        $author_id = get_post_field('post_author', $post_id);

        return get_the_author_meta('display_name', $author_id);
    }
}
