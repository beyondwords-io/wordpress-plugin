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
 * Define plugin constants for tests.
 *
 * Two sources, in priority order:
 *   1. Environment variables (set by CI, e.g. via GitHub Actions secrets).
 *   2. The `config` block of `.wp-env.tests.override.json` (used locally —
 *      the WP test framework uses its own `wp-tests-config.php`, so the
 *      `define()` calls wp-env injects into wp-config.php don't reach us).
 */
$bw_constants = array(
	'BEYONDWORDS_API_URL',
	'BEYONDWORDS_MOCK_API',
	'BEYONDWORDS_TESTS_API_KEY',
	'BEYONDWORDS_TESTS_CONTENT_ID',
	'BEYONDWORDS_TESTS_PROJECT_ID',
);

$bw_override_config = array();
$bw_override_path   = dirname( __DIR__, 2 ) . '/.wp-env.tests.override.json';
if ( file_exists( $bw_override_path ) ) {
	$bw_override_json   = json_decode( (string) file_get_contents( $bw_override_path ), true );
	$bw_override_config = is_array( $bw_override_json['config'] ?? null ) ? $bw_override_json['config'] : array();
}

foreach ( $bw_constants as $bw_const ) {
	if ( defined( $bw_const ) ) {
		continue;
	}

	$bw_value = getenv( $bw_const );
	if ( '' === $bw_value || false === $bw_value ) {
		$bw_value = $bw_override_config[ $bw_const ] ?? null;
	}

	if ( null === $bw_value || '' === $bw_value ) {
		continue;
	}

	if ( 'BEYONDWORDS_MOCK_API' === $bw_const ) {
		$bw_value = filter_var( $bw_value, FILTER_VALIDATE_BOOLEAN );
		if ( ! $bw_value ) {
			continue;
		}
	}

	define( $bw_const, $bw_value );
}

unset( $bw_constants, $bw_override_config, $bw_override_path, $bw_override_json, $bw_const, $bw_value );

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
	require dirname( __DIR__ ) . '/fixtures/wp-content/plugins/beyondwords-mock-rest-api-responses/mock-rest-api-responses.php';
}

// Load base TestCase class
require __DIR__ . '/TestCase.php';
