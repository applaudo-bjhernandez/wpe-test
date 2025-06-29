<?php

defined( 'ABSPATH' ) || exit;

use Dotenv\Dotenv;

$plugin_root = '/';

if ( ! defined( 'WPMDB_PLUGIN_PATH' ) ) {
	define( 'WPMDB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( file_exists( __DIR__ . $plugin_root . ".env" ) ) {
	$dotenv = Dotenv::createImmutable( __DIR__ . $plugin_root );
	$dotenv->load();
}

if ( ! defined( 'WPMDB_MINIMUM_WP_VERSION' ) ) {
	define( 'WPMDB_MINIMUM_WP_VERSION', '5.0' );
}

if ( ! defined( 'WPMDB_MINIMUM_PHP_VERSION' ) ) {
	define( 'WPMDB_MINIMUM_PHP_VERSION', '5.6' );
}

// Silence WP 6.2 Requests library autoloader deprecation warnings
// https://make.wordpress.org/core/2023/03/08/requests-library-upgraded-to-2-0-5-in-wordpress-6-2/
if ( ! defined( 'REQUESTS_SILENCE_PSR0_DEPRECATIONS' ) ) {
	define( 'REQUESTS_SILENCE_PSR0_DEPRECATIONS', true );
}

if ( ! class_exists( 'WPMDB_Requirements_Checker' ) ) {
	require_once __DIR__ . '/requirements-checker.php';
}

$php_checker = new WPMDB_Requirements_Checker( $wpmdb_base_file, WPMDB_MINIMUM_PHP_VERSION, WPMDB_MINIMUM_WP_VERSION );

if ( ! function_exists( 'wpmdb_deactivate_other_instances' ) ) {
	require_once __DIR__ . '/class/deactivate.php';
}

add_action( 'activated_plugin', 'wpmdb_deactivate_other_instances' );
add_action( 'wpmdb_migration_complete', 'wpmdb_deactivate_free_instance_after_migration', 10, 1 );
