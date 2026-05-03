<?php

declare( strict_types = 1 );

namespace BeyondWords\Post;

/**
 * General Post class.
 *
 * @package    Beyondwords
 * @subpackage Beyondwords/includes
 * @author     Stuart McAlpine <stu@beyondwords.io>
 * @since      6.0.0
 * @since      7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class Head
{
    /**
     * Init.
     *
     * @since 6.0.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     */
    public static function init()
    {
        add_action('wp_head', [self::class, 'add_meta_tags']);
    }

    /**
     * Sets meta[beyondwords-*] tags in the head tag of singular pages.
     * We set both the [content] attribute and a custom data attribute for compatibility.
     *
     * @since 6.0.0
     * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
     *
     * @return void
     */
    public static function add_meta_tags()
    {
        if (! is_singular()) {
            return;
        }

        $post_id = get_queried_object_id();

        if (! $post_id) {
            return;
        }

        $project_id = Meta::get_project_id($post_id, true);

        if (! $project_id) {
            return;
        }

        $title = get_the_title($post_id);

        printf(
            '<meta name="beyondwords-title" content="%s" data-beyondwords-title="%s" />' . "\n",
            esc_attr($title),
            esc_attr($title)
        );

        $author_name = get_the_author_meta('display_name', get_post_field('post_author', $post_id));

        printf(
            '<meta name="beyondwords-author" content="%s" data-beyondwords-author="%s" />' . "\n",
            esc_attr($author_name),
            esc_attr($author_name)
        );

        $publish_date = get_the_date('c', $post_id);

        printf(
            '<meta name="beyondwords-publish-date" content="%s" data-beyondwords-publish-date="%s" />' . "\n",
            esc_attr($publish_date),
            esc_attr($publish_date)
        );

        $title_voice_id = get_post_meta($post_id, 'beyondwords_title_voice_id', true);

        if ($title_voice_id) {
            printf(
                '<meta name="beyondwords-title-voice-id" content="%d" data-beyondwords-title-voice-id="%d" />' . "\n",
                esc_attr($title_voice_id),
                esc_attr($title_voice_id)
            );
        }

        $body_voice_id = get_post_meta($post_id, 'beyondwords_body_voice_id', true);

        if ($body_voice_id) {
            printf(
                '<meta name="beyondwords-body-voice-id" content="%d" data-beyondwords-body-voice-id="%d" />' . "\n",
                esc_attr($body_voice_id),
                esc_attr($body_voice_id)
            );
        }

        $summary_voice_id = get_post_meta($post_id, 'beyondwords_summary_voice_id', true);

        if ($summary_voice_id) {
            printf(
                '<meta name="beyondwords-summary-voice-id" content="%d" data-beyondwords-summary-voice-id="%d" />' . "\n", // phpcs:ignore Generic.Files.LineLength.TooLong
                esc_attr($summary_voice_id),
                esc_attr($summary_voice_id)
            );
        }

        $language_code = get_post_meta($post_id, 'beyondwords_language_code', true);

        if ($language_code) {
            printf(
                '<meta name="beyondwords-article-language" content="%s" data-beyondwords-article-language="%s" />' . "\n", // phpcs:ignore Generic.Files.LineLength.TooLong
                esc_attr($language_code),
                esc_attr($language_code)
            );
        }
    }
}
