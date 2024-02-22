<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress;

use Beyondwords\Wordpress\Compatibility\Elementor\Elementor;
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
     */
    public function init()
    {
        // First, run plugin update checks
        (new Updater())->run();

        // Elementor
        (new Elementor())->init();

        // 1. Core
        $this->core = new Core($this->apiClient);
        $this->core->init();

        // 2. Player
        if (SettingsUtils::useLegacyPlayer()) {
            (new LegacyPlayer())->init();
        } else {
            (new Player())->init();
        }

        // 3. Settings
        (new Settings($this->apiClient))->init();

        // 4. Posts screen
        (new Column())->init();
        (new BulkEdit())->init();
        (new BulkEditNotices())->init();

        // 5. Post screen
        (new AddPlayer())->init();
        (new BlockAttributes())->init();
        (new ErrorNotice())->init();
        (new Inspect())->init();

        // 6. Post screen Metabox
        (new GenerateAudio())->init();
        (new DisplayPlayer())->init();
        (new SelectVoice($this->apiClient))->init();
        (new PlayerStyle())->init();
        (new Metabox($this->apiClient))->init();

        // 7. Site Health
        (new SiteHealth())->init();
    }
}
