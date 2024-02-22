<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Compatibility\Elementor\ControlsSections;


use Elementor\Controls_Manager;
use Elementor\Core\DocumentTypes\PageBase as PageBase;

use WP_Post;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;

/**
 * BeyondWords Controls Section
 *
 * @codeCoverageIgnore
 *
 * @since 3.10.0
 */
class Beyondwords
{
    /**
     * Register additional document controls.
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

        $projectId     = PostMetaUtils::getProjectId($post->ID);
        $contentId     = PostMetaUtils::getContentId($post->ID);
        $generateAudio = PostMetaUtils::hasGenerateAudio($post->ID);
        $disabled      = PostMetaUtils::getDisabled($post->ID);

        $document->start_controls_section(
            'beyondwords_status_section',
            [
                'label' => __('BeyondWords', 'speechkit'),
                'tab'   => $tab,
            ]
        );

        $document->add_control(
            'control_beyondwords_project_id',
            [
                'label' => 'Project ID',
                'type' => Controls_Manager::HIDDEN,
                'default' => $projectId,
                'render_type' => 'template',
            ]
        );

        $document->add_control(
            'control_beyondwords_content_id',
            [
                'label' => 'Content ID',
                'type' => Controls_Manager::HIDDEN,
                'default' => $contentId,
                'render_type' => 'template',
            ]
        );

        $document->add_control(
            'control_beyondwords_generate_audio',
            [
                'separator' => 'none',
                'label' => __('Generate audio', 'speechkit'),
                'type' => Controls_Manager::SWITCHER,
                'default' => $generateAudio ? 'yes' : '',
                'render_type' => 'template',
                'conditions' => [
                    'relation' => 'and',
                    'terms' => [
                        ['name' => 'control_beyondwords_content_id', 'operator' => '==', 'value' => ''],
                    ],
                ],
            ]
        );

        $document->add_control(
            'control_beyondwords_player',
            [
                'separator' => 'none',
                'content_id' => $contentId,
                'project_id' => $projectId,
                'type' => 'beyondwords_player',
                'render_type' => 'template',
            ]
        );

        $document->add_control(
            'control_beyondwords_display_player',
            [
                'separator' => 'none',
                'label' => __('Display player', 'speechkit'),
                'type' => Controls_Manager::SWITCHER,
                'default' => $disabled ? '' : 'yes',
                'render_type' => 'template',
                'conditions' => [
                    'relation' => 'or',
                    'terms' => [
                        ['name' => 'control_beyondwords_generate_audio', 'operator' => '==', 'value' => 'yes'],
                        ['name' => 'control_beyondwords_content_id', 'operator' => '!=', 'value' => ''],
                    ],
                ],
            ]
        );

        $document->end_controls_section();
    }
}
