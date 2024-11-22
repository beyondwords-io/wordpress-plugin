<?php

declare(strict_types=1);

/**
 * Settings > BeyondWords > Player
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
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
 * @since 5.0.0
 */
class Player
{
    /**
     * Init
     *
     * @since 5.0.0
     */
    public function init()
    {
        (new PlayerUI())->init();
        (new PlayerStyle())->init();
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
     * @since 5.0.0
     */
    public function addSettingsSection()
    {
        add_settings_section(
            'player',
            __('Player', 'speechkit'),
            array($this, 'sectionCallback'),
            'beyondwords_player',
        );

        $toggledSectionArgs = [
            'before_section' => '<div class="%s">',
            'after_section'  => '</div>',
            'section_class'  => 'beyondwords-settings__player-field-toggle'
        ];

        add_settings_section(
            'styling',
            __('Styling', 'speechkit'),
            false,
            'beyondwords_player',
            $toggledSectionArgs,
        );

        add_settings_section(
            'widget',
            __('Widget', 'speechkit'),
            false,
            'beyondwords_player',
            $toggledSectionArgs,
        );

        add_settings_section(
            'playback-controls',
            __('Playback controls', 'speechkit'),
            false,
            'beyondwords_player',
            $toggledSectionArgs,
        );
    }

    /**
     * Section callback
     *
     * @since 5.0.0
     *
     * @return void
     **/
    public function sectionCallback()
    {
        ?>
        <p class="description">
            <?php
            esc_html_e(
                'By default, these settings are applied to the BeyondWords player for all existing and future posts.',
                'speechkit'
            );
            ?>
        </p>
        <p class="description">
            <?php
            printf(
                /* translators: %s is replaced with the beyondwords_player_sdk_params docs link */
                esc_html__('Unique player settings per-post is supported via the %s filter.', 'speechkit'),
                sprintf(
                    '<a href="https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/wordpress-filters/beyondwords_player_sdk_params" target="_blank" rel="nofollow">%s</a>', // phpcs:ignore Generic.Files.LineLength.TooLong
                    'beyondwords_player_sdk_params'
                )
            );
            ?>
        </p>
        <?php
    }
}
