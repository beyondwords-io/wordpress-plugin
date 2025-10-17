<?php

declare(strict_types=1);

/**
 * BeyondWords Environment.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Core;

/**
 * Environment
 *
 * @since 3.0.0
 */
class Environment
{
    /**
     * The BeyondWords API URL.
     *
     * Override with BEYONDWORDS_API_URL in wp-config.php.
     *
     * @since  3.0.0
     * @var    string
     */
    public const BEYONDWORDS_API_URL = 'https://api.beyondwords.io/v1';

    /**
     * The BeyondWords Backend URL.
     *
     * Override with BEYONDWORDS_BACKEND_URL in wp-config.php.
     *
     * @since  3.0.0
     * @var    string
     */
    public const BEYONDWORDS_BACKEND_URL = '';

    /**
     * The BeyondWords JS SDK URL.
     *
     * Override with BEYONDWORDS_JS_SDK_URL in wp-config.php.
     *
     * @since  3.0.0
     * @var    string
     */
    public const BEYONDWORDS_JS_SDK_URL = 'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js'; // phpcs:ignore Generic.Files.LineLength.TooLong

    /**
     * The BeyondWords AMP Player URL.
     *
     * Override with BEYONDWORDS_AMP_PLAYER_URL in wp-config.php.
     *
     * @since  3.0.0
     * @var    string
     */
    public const BEYONDWORDS_AMP_PLAYER_URL = 'https://audio.beyondwords.io/amp/%d?podcast_id=%s';

    /**
     * The BeyondWords AMP image URL.
     *
     * Override with BEYONDWORDS_AMP_IMG_URL in wp-config.php.
     *
     * @since  3.0.0 Introduced
     * @since  5.3.0 Update asset URL to Azure Storage
     * @var    string
     */
    public const BEYONDWORDS_AMP_IMG_URL = 'https://beyondwords-cdn-b7fyckdeejejb6dj.a03.azurefd.net/assets/logo.svg';

    /**
     * The BeyondWords dashboard URL.
     *
     * Override with BEYONDWORDS_DASHBOARD_URL in wp-config.php.
     *
     * @since  3.0.0
     * @var    string
     */
    public const BEYONDWORDS_DASHBOARD_URL = 'https://dash.beyondwords.io';

    /**
     * Auto-sync settings.
     *
     * @since  5.2.0
     * @var    bool
     */
    public const BEYONDWORDS_AUTO_SYNC_SETTINGS = true;

    /**
     * @return string
     */
    public static function getApiUrl(): string
    {
        if (defined('BEYONDWORDS_API_URL') && strlen(BEYONDWORDS_API_URL)) {
            return BEYONDWORDS_API_URL;
        }

        return static::BEYONDWORDS_API_URL;
    }

    /**
     * @return string
     */
    public static function getBackendUrl(): string
    {
        if (defined('BEYONDWORDS_BACKEND_URL') && strlen(BEYONDWORDS_BACKEND_URL)) {
            return BEYONDWORDS_BACKEND_URL;
        }

        return static::BEYONDWORDS_BACKEND_URL;
    }

    /**
     * @return string
     */
    public static function getJsSdkUrl(): string
    {
        if (defined('BEYONDWORDS_JS_SDK_URL') && strlen(BEYONDWORDS_JS_SDK_URL)) {
            return BEYONDWORDS_JS_SDK_URL;
        }

        return static::BEYONDWORDS_JS_SDK_URL;
    }

    /**
     * @return string
     */
    public static function getAmpPlayerUrl(): string
    {
        if (defined('BEYONDWORDS_AMP_PLAYER_URL') && strlen(BEYONDWORDS_AMP_PLAYER_URL)) {
            return BEYONDWORDS_AMP_PLAYER_URL;
        }

        return static::BEYONDWORDS_AMP_PLAYER_URL;
    }

    /**
     * @return string
     */
    public static function getAmpImgUrl(): string
    {
        if (defined('BEYONDWORDS_AMP_IMG_URL') && strlen(BEYONDWORDS_AMP_IMG_URL)) {
            return BEYONDWORDS_AMP_IMG_URL;
        }

        return static::BEYONDWORDS_AMP_IMG_URL;
    }

    /**
     * @return string
     */
    public static function getDashboardUrl(): string
    {
        if (defined('BEYONDWORDS_DASHBOARD_URL') && strlen(BEYONDWORDS_DASHBOARD_URL)) {
            return BEYONDWORDS_DASHBOARD_URL;
        }

        return static::BEYONDWORDS_DASHBOARD_URL;
    }

    /**
     * @return bool
     */
    public static function hasAutoSyncSettings(): bool
    {
        $value = static::BEYONDWORDS_AUTO_SYNC_SETTINGS;

        if (defined('BEYONDWORDS_AUTO_SYNC_SETTINGS')) {
            $value = (bool) BEYONDWORDS_AUTO_SYNC_SETTINGS;
        }

        return $value;
    }
}
