<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Compatibility\Elementor\ControlsSections;

use Elementor\Controls_Manager;
use Elementor\Core\DocumentTypes\PageBase as PageBase;

use WP_Post;

use Beyondwords\Wordpress\Compatibility\Elementor\Controls\InspectText;
use Beyondwords\Wordpress\Compatibility\Elementor\Controls\InspectTextarea;
use Beyondwords\Wordpress\Component\Post\PostMetaUtils;

/**
 * Inspect Controls Section
 *
 * @codeCoverageIgnore
 *
 * @since 3.10.0
 */
class Inspect
{
    /**
     * Register additional document controls.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @todo replace with `$post = get_post();` with `$document->get_post();`?
     *
     * @param PageBase $document The PageBase document.
     * @param string   $tab      The Elementor tab.
     */
    public static function registerControls(&$document, $tab)
    {
        // PageBase is the base class for documents like `post` `page` and etc.
        if (! $document instanceof PageBase || ! $document::get_property('has_elements')) {
            return;
        }

        $post = get_post();

        if (!($post instanceof \WP_Post)) {
            return;
        }

        $contentId      = PostMetaUtils::getContentId($post->ID);
        $languageId     = PostMetaUtils::getLanguageId($post->ID);
        $projectId      = PostMetaUtils::getProjectId($post->ID);
        $bodyVoiceId    = PostMetaUtils::getBodyVoiceId($post->ID);
        $titleVoiceId   = PostMetaUtils::getTitleVoiceId($post->ID);
        $summaryVoiceId = PostMetaUtils::getSummaryVoiceId($post->ID);

        $document->start_controls_section(
            'beyondwords_inspect_section',
            [
                'label' => __('Inspect', 'speechkit'),
                'tab'   => $tab,
            ]
        );

        // post_id
        $document->add_control(
            'inspect_post_id',
            [
                'label' => 'post_id',
                'label_block' => true,
                'default' => $post->ID,
                'type' => Controls_Manager::TEXT,
            ]
        );

        // beyondwords_generate_audio
        $document->add_control(
            'inspect_beyondwords_generate_audio',
            [
                'label' => 'beyondwords_generate_audio',
                'label_block' => true,
                'default' => get_post_meta($post->ID, 'beyondwords_generate_audio', true),
                'type' => Controls_Manager::TEXT,
            ]
        );

        // beyondwords_project_id
        $document->add_control(
            'inspect_beyondwords_project_id',
            [
                'label' => 'beyondwords_project_id',
                'label_block' => true,
                'default' => $projectId,
                'type' => Controls_Manager::TEXT,
            ]
        );

        // beyondwords_content_id
        $document->add_control(
            'inspect_beyondwords_content_id',
            [
                'label' => 'beyondwords_content_id',
                'label_block' => true,
                'default' => $contentId,
                'type' => Controls_Manager::TEXT,
            ]
        );

        // beyondwords_language_id
        $document->add_control(
            'inspect_beyondwords_language_id',
            [
                'label' => 'beyondwords_language_id',
                'label_block' => true,
                'default' => $languageId,
                'type' => Controls_Manager::TEXT,
            ]
        );

        // beyondwords_body_voice_id
        $document->add_control(
            'inspect_beyondwords_body_voice_id',
            [
                'label' => 'beyondwords_body_voice_id',
                'label_block' => true,
                'default' => $bodyVoiceId,
                'type' => Controls_Manager::TEXT,
            ]
        );

        // beyondwords_title_voice_id
        $document->add_control(
            'inspect_beyondwords_title_voice_id',
            [
                'label' => 'beyondwords_title_voice_id',
                'label_block' => true,
                'default' => $titleVoiceId,
                'type' => Controls_Manager::TEXT,
            ]
        );

        // beyondwords_summary_voice_id
        $document->add_control(
            'inspect_beyondwords_summary_voice_id',
            [
                'label' => 'beyondwords_summary_voice_id',
                'label_block' => true,
                'default' => $summaryVoiceId,
                'type' => Controls_Manager::TEXT,
            ]
        );

        // beyondwords_access_key
        $document->add_control(
            'inspect_beyondwords_access_key',
            [
                'label' => 'beyondwords_access_key',
                'label_block' => true,
                'default' => get_post_meta($post->ID, 'beyondwords_access_key', true),
                'type' => Controls_Manager::TEXT,
            ]
        );

        // beyondwords_error_message
        $document->add_control(
            'inspect_beyondwords_error_message',
            [
                'label' => 'beyondwords_error_message',
                'label_block' => true,
                'default' => get_post_meta($post->ID, 'beyondwords_error_message', true),
                'type' => Controls_Manager::TEXT,
            ]
        );

        // beyondwords_disabled
        $document->add_control(
            'inspect_beyondwords_disabled',
            [
                'label' => 'beyondwords_disabled',
                'label_block' => true,
                'default' => get_post_meta($post->ID, 'beyondwords_disabled', true),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $document->add_control(
            'inspect_copy_data',
            [
                'text' => __('Copy', 'speechkit'),
                'type' => Controls_Manager::BUTTON,
                'event' => 'beyondwords:copy-inspect-data'
            ]
        );
        $document->end_controls_section();
    }
}
