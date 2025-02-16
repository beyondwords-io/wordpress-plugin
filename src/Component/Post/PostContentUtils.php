<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Component\Post;

/**
 * BeyondWords Post Content Utilities.
 *
 * @package    Beyondwords
 * @subpackage Beyondwords/includes
 * @author     Stuart McAlpine <stu@beyondwords.io>
 * @since      3.5.0
 */
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
     * @param int|WP_Post $post The WordPress post ID, or post object.
     *
     * @since 4.6.0
     *
     * @return string The content body param.
     */
    public static function getContentBody($post)
    {
        $post = get_post($post);

        if (!($post instanceof \WP_Post)) {
            throw new \Exception(esc_html__('Post Not Found', 'speechkit'));
        }

        $summary = PostContentUtils::getPostSummary($post);
        $body    = PostContentUtils::getPostBody($post);

        if ($summary) {
            $format = PostContentUtils::getPostSummaryWrapperFormat($post);

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
     * @since 4.6.0 Renamed from PostContentUtils::getBody() to PostContentUtils::getPostBody()
     * @since 4.7.0 Remove wpautop filter for block editor API requests.
     * @since 5.0.0 Remove SpeechKit-Start shortcode.
     * @since 5.0.0 Remove beyondwords_content filter.
     *
     * @param int|WP_Post $post The WordPress post ID, or post object.
     *
     * @return string The body (the processed $post->post_content).
     */
    public static function getPostBody($post)
    {
        $post = get_post($post);

        if (!($post instanceof \WP_Post)) {
            throw new \Exception(esc_html__('Post Not Found', 'speechkit'));
        }

        $content = PostContentUtils::getContentWithoutExcludedBlocks($post);

        if (has_blocks($post)) {
            // wpautop breaks our HTML markup when block editor paragraphs are empty
            remove_filter('the_content', 'wpautop');

            // But we still want to remove empty lines
            $content = preg_replace('/^\h*\v+/m', '', $content);
        }

        // Apply the_content filters to handle shortcodes etc
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
     * @param int|WP_Post $post The WordPress post ID, or post object.
     *
     * @since 4.6.0
     *
     * @return string The summary wrapper <div>.
     */
    public static function getPostSummaryWrapperFormat($post)
    {
        $post = get_post($post);

        if (!($post instanceof \WP_Post)) {
            throw new \Exception(esc_html__('Post Not Found', 'speechkit'));
        }

        $summaryVoiceId = intval(get_post_meta($post->ID, 'beyondwords_summary_voice_id', true));

        if ($summaryVoiceId > 0) {
            return '<div data-beyondwords-summary="true" data-beyondwords-voice-id="' . $summaryVoiceId . '">%s</div>';
        }

        return '<div data-beyondwords-summary="true">%s</div>';
    }

    /**
     * Get the post summary for the audio content.
     *
     * @param int|WP_Post $post The WordPress post ID, or post object.
     *
     * @since 4.0.0
     * @since 4.6.0 Renamed from PostContentUtils::getSummary() to PostContentUtils::getPostSummary()
     *
     * @return string The summary.
     */
    public static function getPostSummary($post)
    {
        $post = get_post($post);

        if (!($post instanceof \WP_Post)) {
            throw new \Exception(esc_html__('Post Not Found', 'speechkit'));
        }

        $summary = null;

        // Optionally send the excerpt to the REST API, if the plugin setting has been checked
        $prependExcerpt = get_option('beyondwords_prepend_excerpt');

        if ($prependExcerpt && has_excerpt($post)) {
            // Escape characters
            $summary = htmlentities($post->post_excerpt, ENT_QUOTES | ENT_XHTML);
            // Apply WordPress filters
            $summary = apply_filters('get_the_excerpt', $summary);
            // Convert line breaks into paragraphs
            $summary = trim(wpautop($summary));
        }

        return $summary;
    }

    /**
     * Get the segments for the audio content, ready to be sent to the BeyondWords API.
     *
     * @codeCoverageIgnore
     * THIS METHOD IS CURRENTLY NOT IN USE. Segments cannot currently include HTML
     * formatting tags such as <strong> and <em> so we do not pass segments, we pass
     * a HTML string as the body param instead.
     *
     * @param int|WP_Post $post The WordPress post ID, or post object.
     *
     * @since 4.0.0
     *
     * @return array|null The segments.
     */
    public static function getSegments($post)
    {
        if (! has_blocks($post)) {
            return null;
        }

        $titleSegment = (object) [
            'section' => 'title',
            'text'    => get_the_title($post),
        ];

        $summarySegment = (object) [
            'section' => 'summary',
            'text'    => PostContentUtils::getPostSummary($post),
        ];

        $blocks = PostContentUtils::getAudioEnabledBlocks($post);

        $bodySegments = array_map(function ($block) {
            $marker = null;

            if (isset($block['attrs']) && isset($block['attrs']['beyondwordsMarker'])) {
                $marker = $block['attrs']['beyondwordsMarker'];
            }

            return (object) [
                'section' => 'body',
                'marker'  => $marker,
                'text'    => trim(render_block($block)),
            ];
        }, $blocks);

        // Merge title, summary and body segments
        $segments = array_values(array_merge([$titleSegment], [$summarySegment], $bodySegments));

        // Remove any segments with empty text
        $segments = array_values(array_filter($segments, function ($segment) {
            return (! empty($segment->text));
        }));

        return $segments;
    }

    /**
     * Get the post content without blocks which have been filtered.
     *
     * We have added buttons into the Gutenberg editor to optionally exclude selected
     * blocks from the source text for audio.
     *
     * This method filters all blocks, removing any which have been excluded.
     *
     * @param int|WP_Post $post The WordPress post ID, or post object.
     *
     * @since 3.8.0
     * @since 4.0.0 Replace for loop with array_reduce
     *
     * @return string The post body without excluded blocks.
     */
    public static function getContentWithoutExcludedBlocks($post)
    {
        if (! has_blocks($post)) {
            return trim($post->post_content);
        }

        $blocks = parse_blocks($post->post_content);
        $output = '';

        $blocks = PostContentUtils::getAudioEnabledBlocks($post);

        foreach ($blocks as $block) {
            $marker = $block['attrs']['beyondwordsMarker'] ?? '';

            $output .= PostContentUtils::addMarkerAttribute(
                render_block($block),
                $marker
            );
        }

        return $output;
    }

    /**
     * Get audio-enabled blocks.
     *
     * @param int|WP_Post $post The WordPress post ID, or post object.
     *
     * @since 4.0.0
     * @since 5.0.0 Remove beyondwords_post_audio_enabled_blocks filter.
     *
     * @return array The blocks.
     */
    public static function getAudioEnabledBlocks($post)
    {
        $post = get_post($post);

        if (! ($post instanceof \WP_Post)) {
            return [];
        }

        if (! has_blocks($post)) {
            return [];
        }

        $allBlocks = parse_blocks($post->post_content);

        $blocks = array_filter($allBlocks, function ($block) {
            $enabled = true;

            if (is_array($block['attrs']) && isset($block['attrs']['beyondwordsAudio'])) {
                $enabled = (bool) $block['attrs']['beyondwordsAudio'];
            }

            return $enabled;
        });

        return $blocks;
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
     *
     * @static
     * @param int $postId WordPress Post ID.
     *
     * @return string JSON endoded params.
     **/
    public static function getContentParams($postId)
    {
        $body = [
            'type'         => 'auto_segment',
            'title'        => get_the_title($postId),
            'body'         => PostContentUtils::getContentBody($postId),
            'source_url'   => get_the_permalink($postId),
            'source_id'    => strval($postId),
            'author'       => PostContentUtils::getAuthorName($postId),
            'image_url'    => strval(wp_get_original_image_url(get_post_thumbnail_id($postId))),
            'metadata'     => PostContentUtils::getMetadata($postId),
            'publish_date' => get_post_time(PostContentUtils::DATE_FORMAT, true, $postId),
        ];

        $status = get_post_status($postId);

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
        } elseif (get_option('beyondwords_project_auto_publish_enabled')) {
            $body['published'] = true;
        }

        $bodyVoiceId = intval(get_post_meta($postId, 'beyondwords_body_voice_id', true));

        if ($bodyVoiceId > 0) {
            $body['body_voice_id'] = $bodyVoiceId;
        }

        $titleVoiceId = intval(get_post_meta($postId, 'beyondwords_title_voice_id', true));

        if ($titleVoiceId > 0) {
            $body['title_voice_id'] = $titleVoiceId;
        }

        /**
         * Filters the params we send to the BeyondWords API 'content' endpoint.
         *
         * @since 4.0.0 Introduced as beyondwords_body_params
         * @since 4.3.0 Renamed from beyondwords_body_params to beyondwords_content_params
         *
         * @param array $body   The params we send to the BeyondWords API.
         * @param array $postId WordPress post ID.
         */
        $body = apply_filters('beyondwords_content_params', $body, $postId);

        return wp_json_encode($body);
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
     * @param int $postId Post ID.
     *
     * @return array
     */
    public static function getMetadata($postId)
    {
        $metadata = new \stdClass();

        $taxonomy = PostContentUtils::getAllTaxonomiesAndTerms($postId);

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
     * @param int $postId Post ID.
     *
     * @return array
     */
    public static function getAllTaxonomiesAndTerms($postId)
    {
        $postType = get_post_type($postId);

        $postTypeTaxonomies = get_object_taxonomies($postType);

        $taxonomies = new \stdClass();

        foreach ($postTypeTaxonomies as $postTypeTaxonomy) {
            $terms = get_the_terms($postId, $postTypeTaxonomy);

            if (! empty($terms) && ! is_wp_error($terms)) {
                $taxonomies->{(string)$postTypeTaxonomy} = wp_list_pluck($terms, 'name');
            }
        }

        return $taxonomies;
    }

    /**
     * Get author name for a post.
     *
     * @since 3.10.4
     *
     * @param int $postId Post ID.
     *
     * @return string
     */
    public static function getAuthorName($postId)
    {
        $authorId = get_post_field('post_author', $postId);

        return get_the_author_meta('display_name', $authorId);
    }

    /**
     * Add data-beyondwords-marker attribute to the root elements in a HTML
     * string (typically the rendered HTML of a single block).
     *
     * Checks to see whether we can use WP_HTML_Tag_Processor, or whether we
     * fall back to using DOMDocument to add the marker.
     *
     * @since 4.2.2
     *
     * @param string  $html   HTML.
     * @param string  $marker Marker UUID.
     *
     * @return string HTML.
     */
    public static function addMarkerAttribute($html, $marker)
    {
        if (! $marker) {
            return $html;
        }

        // Prefer WP_HTML_Tag_Processor, introduced in WordPress 6.2
        if (class_exists('WP_HTML_Tag_Processor')) {
            return PostContentUtils::addMarkerAttributeWithHTMLTagProcessor($html, $marker);
        } else {
            return PostContentUtils::addMarkerAttributeWithDOMDocument($html, $marker);
        }
    }

    /**
     * Add data-beyondwords-marker attribute to the root elements in a HTML
     * string using WP_HTML_Tag_Processor.
     *
     * @since 4.0.0
     * @since 4.2.2 Moved from src/Component/Post/BlockAttributes/BlockAttributes.php
     *              to src/Component/Post/PostContentUtils.php
     * @since 4.7.0 Prevent empty data-beyondwords-marker attributes.
     *
     * @param string  $html   HTML.
     * @param string  $marker Marker UUID.
     *
     * @return string HTML.
     */
    public static function addMarkerAttributeWithHTMLTagProcessor($html, $marker)
    {
        if (! $marker) {
            return $html;
        }

        // https://github.com/WordPress/gutenberg/pull/42485
        $tags = new \WP_HTML_Tag_Processor($html);

        if ($tags->next_tag()) {
            $tags->set_attribute('data-beyondwords-marker', $marker);
        }

        return strval($tags);
    }

    /**
     * Add data-beyondwords-marker attribute to the root elements in a HTML
     * string using DOMDocument.
     *
     * This is a fallback, since WP_HTML_Tag_Processor was only shipped with
     * WordPress 6.2 on 19 April 2023.
     *
     * https://make.wordpress.org/core/2022/10/13/whats-new-in-gutenberg-14-3-12-october/
     *
     * Note: It is not ideal to do all the $bodyElement/$fullHtml processing
     * in this method, but without it DOMDocument does not work as expected if
     * there is more than 1 root element. The approach here has been taken from
     * some historic Gutenberg code before they implemented WP_HTML_Tag_Processor:
     *
     * https://github.com/WordPress/gutenberg/blob/6671cef1179412a2bbd4969cbbc82705c7f69bac/lib/block-supports/index.php
     *
     * @since 4.0.0
     * @since 4.2.2 Moved from src/Component/Post/BlockAttributes/BlockAttributes.php
     *              to src/Component/Post/PostContentUtils.php
     * @since 4.7.0 Prevent empty data-beyondwords-marker attributes.
     *
     * @param string  $html   HTML.
     * @param string  $marker Marker UUID.
     *
     * @return string HTML.
     */
    public static function addMarkerAttributeWithDOMDocument($html, $marker)
    {
        if (! $marker) {
            return $html;
        }

        $dom = new \DOMDocument('1.0', 'utf-8');

        $wrappedHtml =
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'
            . $html
            . '</body></html>';

        $success = $dom->loadHTML($wrappedHtml, LIBXML_HTML_NODEFDTD | LIBXML_COMPACT);

        if (! $success) {
            return $html;
        }

        // Structure is like `<html><head/><body/></html>`, so body is the `lastChild` of our document.
        $bodyElement = $dom->documentElement->lastChild;

        $xpath     = new \DOMXPath($dom);
        $blockRoot = $xpath->query('./*', $bodyElement)[0];

        if (empty($blockRoot)) {
            return $html;
        }

        $blockRoot->setAttribute('data-beyondwords-marker', $marker);

        // Avoid using `$dom->saveHtml( $node )` because the node results may not produce consistent
        // whitespace. Saving the root HTML `$dom->saveHtml()` prevents this behavior.
        $fullHtml = $dom->saveHtml();

        // Find the <body> open/close tags. The open tag needs to be adjusted so we get inside the tag
        // and not the tag itself.
        $start = strpos($fullHtml, '<body>', 0) + strlen('<body>');
        $end   = strpos($fullHtml, '</body>', $start);

        return trim(substr($fullHtml, $start, $end - $start));
    }
}
