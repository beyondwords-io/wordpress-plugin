<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress;

use Beyondwords\Wordpress\Compatibility\WPGraphQL\WPGraphQL;
use Beyondwords\Wordpress\Core\Core;
use Beyondwords\Wordpress\Core\Player\Player;
use Beyondwords\Wordpress\Core\Updater;
use Beyondwords\Wordpress\Component\Post\AddPlayer\AddPlayer;
use Beyondwords\Wordpress\Component\Post\BlockAttributes\BlockAttributes;
use Beyondwords\Wordpress\Component\Post\DisplayPlayer\DisplayPlayer;
use Beyondwords\Wordpress\Component\Post\ErrorNotice\ErrorNotice;
use Beyondwords\Wordpress\Component\Post\GenerateAudio\GenerateAudio;
use Beyondwords\Wordpress\Component\Post\Metabox\Metabox;
use Beyondwords\Wordpress\Component\Post\Panel\Inspect\Inspect;
use Beyondwords\Wordpress\Component\Post\PlayerContent\PlayerContent;
use Beyondwords\Wordpress\Component\Post\PlayerStyle\PlayerStyle;
use Beyondwords\Wordpress\Component\Post\Post;
use Beyondwords\Wordpress\Component\Post\SelectVoice\SelectVoice;
use Beyondwords\Wordpress\Component\Posts\Column\Column;
use Beyondwords\Wordpress\Component\Posts\BulkEdit\BulkEdit;
use Beyondwords\Wordpress\Component\Posts\BulkEdit\Notices as BulkEditNotices;
use Beyondwords\Wordpress\Component\Settings\Settings;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Component\SiteHealth\SiteHealth;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Plugin
{
    /**
     * Constructor.
     *
     * @since 3.0.0
     * @since 4.5.1 Disable plugin features if we don't have valid API settings.
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        // Run plugin update checks before anything else
        Updater::run();

        // Third-party plugin/theme compatibility
        WPGraphQL::init();

        // Core
        Core::init();

        // Site health
        SiteHealth::init();

        // Player
        Player::init();

        // Post
        Post::init();

        // Settings
        Settings::init();

        /**
         * To prevent browser JS errors we skip adding admin UI components until
         * we have a valid REST API connection.
         */
        if (SettingsUtils::hasValidApiConnection()) {
            // Posts screen
            BulkEdit::init();
            BulkEditNotices::init();
            Column::init();

            // Post screen
            AddPlayer::init();
            BlockAttributes::init();
            ErrorNotice::init();
            Inspect::init();

            // Post screen metabox
            GenerateAudio::init();
            DisplayPlayer::init();
            SelectVoice::init();
            PlayerContent::init();
            PlayerStyle::init();
            PlayerContent::init();
            Metabox::init();
        }
    }
}
