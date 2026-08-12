<?php
/**
 * Request router for the CI web server (`php -S`), which cannot rewrite.
 *
 * Pretty permalinks are set in cypress.config.js `setupDatabase`, so anything
 * that isn't a real file has to reach WordPress the way Apache's .htaccess
 * fallback does — otherwise `/wp-json/…` and post permalinks 404.
 */

$bw_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

// Let the server handle real files and directory indexes itself.
if ( file_exists( $_SERVER['DOCUMENT_ROOT'] . $bw_path ) ) {
	return false;
}

require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
