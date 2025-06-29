<?php
/*
Plugin Name: WP Engine Site Migration
Description: Migrate any WordPress site to WP Engine or Flywheel. Copy all database tables and site files or customize the migration to include only what you need.
Author: WP Engine
Version: 1.7.0
Author URI: https://wpengine.com/?utm_source=wpesm_plugin&utm_medium=referral&utm_campaign=bx_prod_referral&utm_content=wpesm_plugin_author_link
Network: True
Text Domain: wp-migrate-db
Domain Path: /languages/
*/

// Copyright (c) 2023 WP Engine. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

defined( 'ABSPATH' ) || exit;

require_once 'version-wpe.php';

if ( ! defined( 'WPESM_FILE' ) ) {
	// Defines the path to the main plugin file.
	define( 'WPESM_FILE', __FILE__ );

	// Defines the path to be used for includes.
	define( 'WPESM_PATH', plugin_dir_path( WPESM_FILE ) );
}

if ( ! defined( 'WPMDB_CORE_SLUG' ) ) {
	define( 'WPMDB_CORE_SLUG', 'wpe-site-migration' );
}

if ( ! defined( 'WPMDB_PLUGIN_TITLE' ) ) {
	define( 'WPMDB_PLUGIN_TITLE', 'WP Engine Site Migration' );
}

if ( ! defined( 'WPMDB_PLUGIN_URL' ) ) {
	define( "WPMDB_PLUGIN_URL", plugin_dir_url( WPESM_FILE ) );
}

define( 'WPE_MIGRATIONS', true );

// TODO: Replace with checked-in prefixed libraries >>>
// NOTE: This path is updated during the build process.
$plugin_root = '/';

if ( ! defined( 'WPMDB_VENDOR_DIR' ) ) {
	define( 'WPMDB_VENDOR_DIR', __DIR__ . $plugin_root . "vendor" );
}

require WPMDB_VENDOR_DIR . '/autoload.php';
// TODO: Replace with checked-in prefixed libraries <<<

// This variable is needed by a call within setup-plugin.php.
$wpmdb_base_file = __FILE__;
require 'setup-plugin.php';

if ( version_compare( PHP_VERSION, WPMDB_MINIMUM_PHP_VERSION, '>=' ) ) {
	require_once WPESM_PATH . 'class/autoload.php';
	require_once WPESM_PATH . 'setup-wpe.php';
}

//Setup constants.
require_once 'constants.php';

/**
 * Called when the plugin is deactivated.
 */
function wpe_site_migration_deactivate_plugin() {
	// Remove the compatibility plugin when the plugin is deactivated.
	do_action( 'wp_migrate_db_remove_compatibility_plugin' );
	//Remove migration related options.
	do_action( 'wpmdb_deactivate_plugin' );
}

/**
 * Called when the plugin is deleted.
 */
function wpe_site_migration_delete_plugin() {
	\DeliciousBrains\WPMDB\SiteMigration\Plugin\Scrubber::delete_all_plugin_options();
	\DeliciousBrains\WPMDB\SiteMigration\Plugin\Scrubber::delete_usermeta();
	\DeliciousBrains\WPMDB\Common\Debug::delete_all();
}
