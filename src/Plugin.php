<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress;

use Beyondwords\Wordpress\Compatibility\Elementor\Elementor;
use Beyondwords\Wordpress\Compatibility\WPGraphQL\WPGraphQL;
use Beyondwords\Wordpress\Core\ApiClient;
use Beyondwords\Wordpress\Core\Core;
use Beyondwords\Wordpress\Core\Player\LegacyPlayer;
use Beyondwords\Wordpress\Core\Player\Player;
use Beyondwords\Wordpress\Core\Updater;
use Beyondwords\Wordpress\Component\Post\AddPlayer\AddPlayer;
use Beyondwords\Wordpress\Component\Post\BlockAttributes\BlockAttributes;
use Beyondwords\Wordpress\Component\Post\DisplayPlayer\DisplayPlayer;
use Beyondwords\Wordpress\Component\Post\ErrorNotice\ErrorNotice;
use Beyondwords\Wordpress\Component\Post\GenerateAudio\GenerateAudio;
use Beyondwords\Wordpress\Component\Post\Metabox\Metabox;
use Beyondwords\Wordpress\Component\Post\Panel\Inspect\Inspect;
use Beyondwords\Wordpress\Component\Post\PlayerStyle\PlayerStyle;
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
     * Public property required so that we can run bulk edit actions like this:
     * $beyondwords_wordpress_plugin->core->generateAudioForPost($postId);
     *
     * @see \Beyondwords\Wordpress\Component\Posts\BulkEdit\BulkEdit
     */
    public $core;

    /**
     * Public property required so that we can run bulk edit actions like this:
     * $beyondwords_wordpress_plugin->player->getBody;
     *
     * @see \Beyondwords\Wordpress\Component\Post\PostContentUtils
     */
    public $player;

    /**
     * The API client - this enables various components to access the API.
     *
     * @todo Consider switching from dependency injection to singleton or another
     *       pattern so that components can perform API calls without DI.
     */
    public $apiClient;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * Constructor.
     *
     * @since 3.0.0
     * @since 4.5.1 Disable plugin features if we don't have valid API settings.
     */
    public function init()
    {
        // Run plugin update checks before anything else
        (new Updater())->run();

        // Third-party plugin/theme compatibility
        (new Elementor())->init();
        (new WPGraphQL())->init();

        // Core
        $this->core = new Core($this->apiClient);
        $this->core->init();

        // Site health
        (new SiteHealth())->init();

        // Player
        if (SettingsUtils::useLegacyPlayer()) {
            (new LegacyPlayer())->init();
        } else {
            (new Player())->init();
        }

        // Settings
        (new Settings($this->apiClient))->init();

        /**
         * To prevent browser JS errors we skip adding admin UI components until
         * we have a valid REST API connection.
         */
        if (SettingsUtils::hasApiSettings()) {
            // Posts screen
            (new BulkEdit())->init();
            (new BulkEditNotices())->init();
            (new Column())->init();

            // Post screen
            (new AddPlayer())->init();
            (new BlockAttributes())->init();
            (new ErrorNotice())->init();
            (new Inspect())->init();

            // Post screen metabox
            (new GenerateAudio())->init();
            (new DisplayPlayer())->init();
            (new SelectVoice($this->apiClient))->init();
            (new PlayerStyle())->init();
            (new Metabox($this->apiClient))->init();
        }
    }
}
