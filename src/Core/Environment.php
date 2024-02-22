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

use Beyondwords\Wordpress\Component;

/**
 * Environment setup
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
     * @access private
     * @var    string
     */
    public const BEYONDWORDS_API_URL = 'https://api.beyondwords.io/v1';

    /**
     * The BeyondWords Backend URL.
     *
     * Override with BEYONDWORDS_BACKEND_URL in wp-config.php.
     *
     * @since  3.0.0
     * @access private
     * @var    string
     */
    public const BEYONDWORDS_BACKEND_URL = '';

    /**
     * The BeyondWords JS SDK URL.
     *
     * Override with BEYONDWORDS_JS_SDK_URL in wp-config.php.
     *
     * @since  3.0.0
     * @access private
     * @var    string
     */
    public const BEYONDWORDS_JS_SDK_URL = 'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js'; // phpcs:ignore Generic.Files.LineLength.TooLong

    /**
     * The BeyondWords JS SDK URL (Legacy).
     *
     * Override with BEYONDWORDS_JS_SDK_URL_LEGACY in wp-config.php.
     *
     * @since  4.0.0
     * @access private
     * @var    string
     */
    public const BEYONDWORDS_JS_SDK_URL_LEGACY = 'https://proxy.beyondwords.io/npm/@beyondwords/audio-player@latest/dist/module/index.js'; // phpcs:ignore Generic.Files.LineLength.TooLong

    /**
     * The BeyondWords AMP Player URL.
     *
     * Override with BEYONDWORDS_AMP_PLAYER_URL in wp-config.php.
     *
     * @since  3.0.0
     * @access private
     * @var    string
     */
    public const BEYONDWORDS_AMP_PLAYER_URL = 'https://audio.beyondwords.io/amp/%d?podcast_id=%s';

    /**
     * The BeyondWords AMP image URL.
     *
     * Override with BEYONDWORDS_AMP_IMG_URL in wp-config.php.
     *
     * @since  3.0.0
     * @access private
     * @var    string
     */
    public const BEYONDWORDS_AMP_IMG_URL = 'https://s3-eu-west-1.amazonaws.com/beyondwords-assets/logo.svg';

    /**
     * The BeyondWords dashboard URL.
     *
     * Override with BEYONDWORDS_DASHBOARD_URL in wp-config.php.
     *
     * @since  3.0.0
     * @access private
     * @var    string
     */
    public const BEYONDWORDS_DASHBOARD_URL = 'https://dash.beyondwords.io';

    /**
     * @return string
     */
    public static function getApiUrl()
    {
        if (defined('BEYONDWORDS_API_URL') && strlen(BEYONDWORDS_API_URL)) {
            return BEYONDWORDS_API_URL;
        }

        return static::BEYONDWORDS_API_URL;
    }

    /**
     * @return string
     */
    public static function getBackendUrl()
    {
        if (defined('BEYONDWORDS_BACKEND_URL') && strlen(BEYONDWORDS_BACKEND_URL)) {
            return BEYONDWORDS_BACKEND_URL;
        }

        return static::BEYONDWORDS_BACKEND_URL;
    }

    /**
     * @return string
     */
    public static function getJsSdkUrl()
    {
        if (defined('BEYONDWORDS_JS_SDK_URL') && strlen(BEYONDWORDS_JS_SDK_URL)) {
            return BEYONDWORDS_JS_SDK_URL;
        }

        return static::BEYONDWORDS_JS_SDK_URL;
    }

    /**
     * @return string
     */
    public static function getJsSdkUrlLegacy()
    {
        if (defined('BEYONDWORDS_JS_SDK_URL_LEGACY') && strlen(BEYONDWORDS_JS_SDK_URL_LEGACY)) {
            return BEYONDWORDS_JS_SDK_URL_LEGACY;
        }

        return static::BEYONDWORDS_JS_SDK_URL_LEGACY;
    }

    /**
     * @return string
     */
    public static function getAmpPlayerUrl()
    {
        if (defined('BEYONDWORDS_AMP_PLAYER_URL') && strlen(BEYONDWORDS_AMP_PLAYER_URL)) {
            return BEYONDWORDS_AMP_PLAYER_URL;
        }

        return static::BEYONDWORDS_AMP_PLAYER_URL;
    }

    /**
     * @return string
     */
    public static function getAmpImgUrl()
    {
        if (defined('BEYONDWORDS_AMP_IMG_URL') && strlen(BEYONDWORDS_AMP_IMG_URL)) {
            return BEYONDWORDS_AMP_IMG_URL;
        }

        return static::BEYONDWORDS_AMP_IMG_URL;
    }

    /**
     * @return string
     */
    public static function getDashboardUrl()
    {
        if (defined('BEYONDWORDS_DASHBOARD_URL') && strlen(BEYONDWORDS_DASHBOARD_URL)) {
            return BEYONDWORDS_DASHBOARD_URL;
        }

        return static::BEYONDWORDS_DASHBOARD_URL;
    }
}
