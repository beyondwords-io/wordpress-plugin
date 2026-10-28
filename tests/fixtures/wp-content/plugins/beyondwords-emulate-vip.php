<?php
/**
 * Emulates WordPress VIP's deferred audio generation for tests.
 *
 * @wordpress-plugin
 * Plugin Name:       BeyondWords Test: Emulate VIP
 * Description:       Defers audio generation to cron, and runs cron only when a test asks.
 * Author:            BeyondWords
 * Text Domain:       speechkit
 * License:           No License
 */

add_filter( 'beyondwords_async_generate_audio', '__return_true' );

// VIP runs cron out-of-band, so page loads must never fire the queued job.
remove_action( 'init', 'wp_cron' );
