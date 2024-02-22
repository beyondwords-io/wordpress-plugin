<?php
require dirname( dirname( dirname( __FILE__ ) ) ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

/**
 * PHPUnit bootstrap file.
 *
 * @package Speechkit
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( dirname( __FILE__ ) ) ) . '/speechkit.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

/*
 * Define plugin constants from system env vars
 */

$apiUrl = getenv( 'BEYONDWORDS_API_URL' );
if ( false !== $apiUrl ) {
	define( 'BEYONDWORDS_API_URL', $apiUrl );
}

$testsApiKey = getenv( 'BEYONDWORDS_TESTS_API_KEY' );
if ( false !== $testsApiKey ) {
	define( 'BEYONDWORDS_TESTS_API_KEY', $testsApiKey );
}

$testsContentId = getenv( 'BEYONDWORDS_TESTS_CONTENT_ID' );
if ( false !== $testsContentId ) {
	define( 'BEYONDWORDS_TESTS_CONTENT_ID', $testsContentId );
}

$testsProjectId = getenv( 'BEYONDWORDS_TESTS_PROJECT_ID' );
if ( false !== $testsProjectId ) {
	define( 'BEYONDWORDS_TESTS_PROJECT_ID', $testsProjectId );
}
