<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Compatibility\Elementor\ControlsSections;

use Elementor\Controls_Manager;
use Elementor\Core\DocumentTypes\PageBase as PageBase;

use WP_Post;

/**
 * Help Controls Section
 *
 * @codeCoverageIgnore
 *
 * @since 3.10.0
 */
class Help
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

        $document->start_controls_section(
            'beyondwords_help_section',
            [
                'label' => __('Help', 'speechkit'),
                'tab'   => $tab,
            ]
        );

        $document->add_control(
            'beyondwords_help_guide',
            [
                'text' => __('Setup guide', 'speechkit'),
                'type' => Controls_Manager::BUTTON,
                'label_block' => true,
                'label' => __('For setup instructions, troubleshooting, and FAQs, see our BeyondWords for WordPress guide.', 'speechkit'), // phpcs:ignore Generic.Files.LineLength.TooLong
                'event' => 'beyondwords:open-guide'
            ]
        );

        $document->add_control(
            'beyondwords_help_email',
            [
                'text' => __('Email BeyondWords', 'speechkit'),
                'type' => Controls_Manager::BUTTON,
                'separator' => 'before',
                'label_block' => true,
                'label' => __('Need help? Email our support team.', 'speechkit'),
                'event' => 'beyondwords:email-support'
            ]
        );

        $document->end_controls_section();
    }
}
