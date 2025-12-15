<?php
require dirname( dirname( dirname( __FILE__ ) ) ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

/**
 * PHPUnit bootstrap file.
 *
 * @package Speechkit
 */

// PHP 8.4+: Suppress deprecation warnings from Symfony 5.4 (required for PHP 8.0 support)
// When PHP 8.0 support is dropped, upgrade to Symfony 6.4+ and remove this.
if ( PHP_VERSION_ID >= 80400 ) {
	error_reporting( E_ALL & ~E_DEPRECATED );
}

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

/*
 * Define plugin constants from system env vars (before WP boots)
 */

$apiUrl = getenv( 'BEYONDWORDS_API_URL' );
if ( false !== $apiUrl ) {
	define( 'BEYONDWORDS_API_URL', $apiUrl );
}

$mockApi = getenv( 'BEYONDWORDS_MOCK_API' );
if ( false !== $mockApi && $mockApi ) {
	define( 'BEYONDWORDS_MOCK_API', true );
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

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( dirname( __FILE__ ) ) ) . '/speechkit.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// Load mock API responses plugin if enabled (after WP boots so filters work).
if ( defined( 'BEYONDWORDS_MOCK_API' ) && BEYONDWORDS_MOCK_API ) {
	require dirname( __DIR__ ) . '/fixtures/wp-content/plugins/mock-rest-api-responses.php';
}

// Load base TestCase class
require __DIR__ . '/TestCase.php';
