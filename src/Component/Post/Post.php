<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Component\Post;

/**
 * General Post class.
 *
 * @package    Beyondwords
 * @subpackage Beyondwords/includes
 * @author     Stuart McAlpine <stu@beyondwords.io>
 * @since      6.0.0
 */
class Post
{
    /**
     * Init.
     *
     * @since 6.0.0
     */
    public static function init()
    {
        add_action('wp_head', [self::class, 'addMetaTags']);
    }

    /**
     * Sets meta[beyondwords-*] tags in the head tag of singular pages.
     * We set both the [content] attribute and a custom data attribute for compatibility.
     *
     * @since 6.0.0
     *
     * @return void
     */
    public static function addMetaTags()
    {
        if (! is_singular()) {
            return;
        }

        $postId = get_queried_object_id();

        if (! $postId) {
            return;
        }

        $projectId = PostMetaUtils::getProjectId($postId, true);

        if (! $projectId) {
            return;
        }

        $title = get_the_title($postId);

        printf(
            '<meta name="beyondwords-title" content="%s" data-beyondwords-title="%s" />' . "\n",
            esc_attr($title),
            esc_attr($title)
        );

        $authorName = get_the_author_meta('display_name', get_post_field('post_author', $postId));

        printf(
            '<meta name="beyondwords-author" content="%s" data-beyondwords-author="%s" />' . "\n",
            esc_attr($authorName),
            esc_attr($authorName)
        );

        $publishDate = get_the_date('c', $postId);

        printf(
            '<meta name="beyondwords-publish-date" content="%s" data-beyondwords-publish-date="%s" />' . "\n",
            esc_attr($publishDate),
            esc_attr($publishDate)
        );

        $titleVoiceId = get_post_meta($postId, 'beyondwords_title_voice_id', true);

        if ($titleVoiceId) {
            printf(
                '<meta name="beyondwords-title-voice-id" content="%d" data-beyondwords-title-voice-id="%d" />' . "\n",
                esc_attr($titleVoiceId),
                esc_attr($titleVoiceId)
            );
        }

        $bodyVoiceId = get_post_meta($postId, 'beyondwords_body_voice_id', true);

        if ($bodyVoiceId) {
            printf(
                '<meta name="beyondwords-body-voice-id" content="%d" data-beyondwords-body-voice-id="%d" />' . "\n",
                esc_attr($bodyVoiceId),
                esc_attr($bodyVoiceId)
            );
        }

        $summaryVoiceId = get_post_meta($postId, 'beyondwords_summary_voice_id', true);

        if ($summaryVoiceId) {
            printf(
                '<meta name="beyondwords-summary-voice-id" content="%d" data-beyondwords-summary-voice-id="%d" />' . "\n", // phpcs:ignore Generic.Files.LineLength.TooLong
                esc_attr($summaryVoiceId),
                esc_attr($summaryVoiceId)
            );
        }

        $languageCode = get_post_meta($postId, 'beyondwords_language_code', true);

        if ($languageCode) {
            printf(
                '<meta name="beyondwords-article-language" content="%s" data-beyondwords-article-language="%s" />' . "\n", // phpcs:ignore Generic.Files.LineLength.TooLong
                esc_attr($languageCode),
                esc_attr($languageCode)
            );
        }
    }
}
