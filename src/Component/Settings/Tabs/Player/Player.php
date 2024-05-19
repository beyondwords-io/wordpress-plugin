<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Player
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Tabs\Player;

use Beyondwords\Wordpress\Component\Settings\Fields\CallToAction\CallToAction;
use Beyondwords\Wordpress\Component\Settings\Fields\PlaybackFromSegments\PlaybackFromSegments;
use Beyondwords\Wordpress\Component\Settings\Fields\PlaybackControls\PlaybackControls;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerColors\PlayerColors;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerStyle\PlayerStyle;
use Beyondwords\Wordpress\Component\Settings\Fields\WidgetPosition\WidgetPosition;
use Beyondwords\Wordpress\Component\Settings\Fields\WidgetStyle\WidgetStyle;
use Beyondwords\Wordpress\Component\Settings\Fields\TextHighlighting\TextHighlighting;

/**
 * "Player" settings tab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @since 4.8.0
 */
class Player
{
    /**
     * API client.
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @since 4.8.0
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Init
     */
    public function init()
    {
        (new PlayerUI())->init();
        (new PlayerStyle($this->apiClient))->init();
        (new PlayerColors())->init();
        (new CallToAction())->init();
        (new WidgetStyle())->init();
        (new WidgetPosition())->init();
        (new TextHighlighting())->init();
        (new PlaybackFromSegments())->init();
        (new PlaybackControls())->init();

        add_action('admin_init', array($this, 'addSettingsSection'), 5);
    }

    /**
     * Add Settings sections.
     *
     * @since  4.8.0
     */
    public function addSettingsSection()
    {
        add_settings_section(
            'player',
            __('Player', 'speechkit'),
            array($this, 'sectionCallback'),
            'beyondwords_player',
        );

        add_settings_section(
            'playback-controls',
            __('Playback controls', 'speechkit'),
            false,
            'beyondwords_player',
        );
    }

    /**
     * Section callback
     *
     * @since 4.8.0
     *
     * @return void
     **/
    public function sectionCallback()
    {
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'Do we want a description for consistency?', // phpcs:ignore Generic.Files.LineLength.TooLong
                'speechkit'
            );
            ?>
        </p>
        <?php
    }
}
