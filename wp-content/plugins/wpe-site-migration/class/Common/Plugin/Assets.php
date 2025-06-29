<?php

namespace DeliciousBrains\WPMDB\Common\Plugin;

use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Util\Util;

class  Assets {

	public $assets, $http, $filesystem, $settings, $props;

	/**
	 * @var ErrorLog
	 */
	private $error_log;

	/**
	 * @var Util
	 */
	private $util;

	public function __construct(
		Http $http,
		ErrorLog $error_log,
		Filesystem $filesystem,
		Properties $properties,
		Settings $settings,
		Util $util
	) {
		$this->http       = $http;
		$this->filesystem = $filesystem;
		$this->props      = $properties;
		$this->error_log  = $error_log;
		$this->settings   = $settings;
		$this->util       = $util;
	}

	public function register() {
		add_action( 'admin_enqueue_scripts', [ $this, 'wpmdb_enqueue_assets' ] );
	}

	/**
	 * Checks and sets up plugin assets.
	 * Filter actions, enqueue scripts, define configuration, and constants.
	 *
	 * @return void
	 */
	function load_assets() {
		$this->http->http_verify_download();

		$log = $this->error_log;
		$log->http_prepare_download_log();

		// add our custom CSS classes to <body>
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );

		$plugins_url = trailingslashit( plugins_url( $this->props->plugin_folder_name ) );
		$version     = \defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->props->plugin_version;

		do_action( 'wpmdb_load_assets' );
		$src = $plugins_url . "frontend/mdb-2.0.js";
		wp_enqueue_script( 'wp-migrate-db-pro-script-v2', $src, [], $version, true );

		wp_localize_script(
			'wp-migrate-db-pro-script-v2',
			'wpmdb_settings',
			apply_filters( 'wpmdb_settings_js', $this->settings->get_settings_for_frontend() )
		);

		wp_localize_script(
			'wp-migrate-db-pro-script-v2',
			'wpmdb_strings',
			apply_filters(
				'wpmdb_js_strings',
				array(
					'connection_info_missing'     => __(
						'The connection information appears to be missing, please enter it to continue.',
						'wp-migrate-db'
					),
					'connection_info_url_invalid' => __(
						'The URL on the first line appears to be invalid, please check it and try again.',
						'wp-migrate-db'
					),
					'connection_info_key_invalid' => __(
						'The secret key on the second line appears to be invalid. It should be a 40 character string that consists of letters, numbers and special characters only.',
						'wp-migrate-db'
					),
					'connection_info_local_url'   => __(
						"It appears you've entered connection information for this site. Instead you need to provide connection information from the Settings tab of the remote site to which you are trying to connect.",
						'wp-migrate-db'
					),
					'connection_info_local_key'   => __(
						'Looks like your remote secret key is the same as the secret key for this site. To fix this, go to the <a href="#settings">Settings tab</a> and click "Reset Secret Key"',
						'wp-migrate-db'
					),
					'migration_cancelled'         => _x(
						'Migration canceled',
						'The migration has been canceled',
						'wp-migrate-db'
					),
					'migration_cancelled_success' => __(
						'The migration has been canceled. Any temporary database tables or files created by the migration have been cleaned up.',
						'wp-migrate-db'
					),
					'welcome_title'               => __( 'Welcome to WP Migrate! &#127881;', 'wp-migrate-db' ),
					'welcome_text'                => __(
						'Hey, this is the first time activating your license, nice! Your migrations are about to get awesome! If you haven’t already, you should check out our <a href="%1$s" target="_blank">Quick Start Guide</a> and <a href="%2$s" target="_blank">Videos</a>. If you run into any trouble at all, use the <strong>Help tab</strong> above to submit a support request.',
						'wp-migrate-db'
					),
					'invalid_sql_file'            => __(
						'The selected file does not have a recognized file type. Please upload a valid SQL file to continue.',
						'wp-migrate-db'
					),
					'please_select_sql_file'      => __(
						'Please select an SQL export file above to continue.',
						'wp-migrate-db'
					),
					'importing_file_to_db'        => __( 'Importing data from %s', 'wp-migrate-db' ),
					'plugins_url'                 => network_admin_url( 'plugins.php' ),
					'permalinks_link'             => network_admin_url( 'options-permalink.php' ),
				)
			)
		);
	}

	function localize_notification_strings() {
		wp_localize_script(
			'wp-migrate-db-pro-script-v2',
			'wpmdb_notifications',
			apply_filters( 'wpmdb_notification_strings', [] )
		);
	}

	function admin_body_class( $classes ) {
		if ( ! $classes ) {
			$classes = array();
		} else {
			$classes = explode( ' ', $classes );
		}

		$version_class = 'wpmdb-not-pro';
		if ( true == $this->props->is_pro ) {
			$version_class = 'wpmdb-pro';
		}

		$classes[] = $version_class;

		// Recommended way to target WP 3.8+
		// http://make.wordpress.org/ui/2013/11/19/targeting-the-new-dashboard-design-in-a-post-mp6-world/
		if ( version_compare( $GLOBALS['wp_version'], '3.8-alpha', '>' ) ) {
			if ( ! in_array( 'mp6', $classes ) ) {
				$classes[] = 'mp6';
			}
		}

		return implode( ' ', $classes );
	}

	public function get_saved_migration_profiles() {
		$raw_data = get_site_option( WPMDB_SAVED_PROFILES_OPTION );

		if ( empty( $raw_data ) ) {
			return [];
		}

		$profiles = array_map(
			function ( $key, $item ) {
				return [
					'id'             => $key,
					'guid'           => $item['guid'],
					'name'           => $item['name'],
					'last_migration' => $this->get_last_migration_utc_timestamp( $item ),
				];
			},
			array_keys( $raw_data ),
			$raw_data
		);

		usort( $profiles, function ( $a, $b ) {
			if ( $a['last_migration'] == $b['last_migration'] ) {
				return 0;
			}
			return ( $a['last_migration'] > $b['last_migration'] ) ? -1 : 1;
		});

		return array_values( $profiles );
	}


	/**
	 * Gets the last migration date from the migration data.
	 * 
	 * The last migration is stored in UTC format "2025-03-10T15:58:07.239Z".
	 *
	 * @param array $item
	 * 
	 * @return mixed|null Last migration UTC timestamp or null if unavailable.
	 */
	private function get_last_migration_utc_timestamp( $item ) {
		if ( ! isset( $item['value'] ) || ! is_string( $item['value'] ) ) {
			return null;
		}

		$value = json_decode( $item['value'] );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_object( $value ) ) {
			return null;
		}

		if ( ! isset( $value->media_files->last_migration ) || $value->media_files->last_migration === '' ) {
			return null;
		}

		return $value->media_files->last_migration;
	}

	public function get_recent_migrations( $raw_data ) {
		if ( empty( $raw_data ) ) {
			return [];
		}

		$migrations = array_map(
			function ( $key, $item ) {
				return [
					'id'   => $key,
					'name' => $item['name'],
					'date' => $item['date'],
					'guid' => $item['guid'],
				];
			},
			array_keys( $raw_data ),
			$raw_data
		);

		usort( $migrations, function ( $a, $b ) {
			return $a < $b ? 1 : 0;
		} );

		return $migrations;
	}

	public function wpmdb_enqueue_assets() {
		$is_dev = isset( $_ENV['MDB_IS_DEV'] ) ? (bool) $_ENV['MDB_IS_DEV'] : false;

		$slug = '/wp-migrate-db';
		if ( defined( "WPE_MIGRATIONS" ) && WPE_MIGRATIONS ) {
			$slug = '/wpe-site-migration';
		}
		//always load pro when in dev env
		if ( defined( "WPMDB_PRO" ) && WPMDB_PRO || $is_dev ) {
			$slug = '/wp-migrate-db-pro';
		}

		// @TODO update when Multisite enabled and free version ready
		if ( $this->util->isMDBPage() && Util::is_wp_compatible() ) {
			$assets_path = apply_filters( 'wpmdb_frontend_assets_path', WP_PLUGIN_DIR . $slug . '/frontend' );
			\ReactWPScripts\enqueue_assets(
				$assets_path,
				[
					'base_url' => plugins_url( $slug . '/frontend' ),
				]
			);

			do_action( 'wpmdb_load_frontend' );
		}
	}
}
