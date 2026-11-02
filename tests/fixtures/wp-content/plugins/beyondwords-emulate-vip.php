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

/**
 * Hide our queued job from page-load cron, leaving every other event alone.
 *
 * VIP runs cron out-of-band; tests fire the job via `wp cron event run`.
 */
function beyondwords_emulate_vip_ready_cron_jobs() {
	remove_filter( 'pre_get_ready_cron_jobs', __FUNCTION__ );
	$jobs = wp_get_ready_cron_jobs();
	add_filter( 'pre_get_ready_cron_jobs', __FUNCTION__, 10, 0 );

	foreach ( $jobs as $timestamp => $hooks ) {
		unset( $hooks['beyondwords_generate_audio'] );

		if ( empty( $hooks ) ) {
			unset( $jobs[ $timestamp ] );
		} else {
			$jobs[ $timestamp ] = $hooks;
		}
	}

	return $jobs;
}
add_filter( 'pre_get_ready_cron_jobs', 'beyondwords_emulate_vip_ready_cron_jobs', 10, 0 );
