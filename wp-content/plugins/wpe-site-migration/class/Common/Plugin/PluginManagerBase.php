<?php

namespace DeliciousBrains\WPMDB\Common\Plugin;

use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\WPMDBRestAPIServer;
use DeliciousBrains\WPMDB\Common\Migration\MigrationHelper;
use DeliciousBrains\WPMDB\Common\Multisite\Multisite;
use DeliciousBrains\WPMDB\Common\Profile\ProfileManager;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Sanitize;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Sql\Table;
use DeliciousBrains\WPMDB\Common\UI\Notice;
use DeliciousBrains\WPMDB\Common\Upgrades\UpgradeRoutinesManager;
use DeliciousBrains\WPMDB\Common\Util\Util;
use WP_Error;

/**
 * Class PluginManager
 *
 * @package DeliciousBrains\WPMDB\Common\Plugin
 */
class PluginManagerBase {

	const DBRAINS_URL = 'https://deliciousbrains.com';
	const WPE_URL     = 'https://wpengine.com';

	/**
	 * @var Properties
	 */
	public $props;

	/**
	 * @var $settings
	 */
	public $settings;

	/**
	 * @var Assets
	 */
	public $assets;

	/**
	 * @var Util
	 */
	public $util;

	/**
	 * @var Table
	 */
	public $tables;

	/**
	 * @var Http
	 */
	public $http;

	/**
	 * @var Filesystem
	 */
	public $filesystem;

	/**
	 * @var
	 */
	public $addon;

	/**
	 * @var Multisite
	 */
	public $multisite;

	/**
	 * @var MigrationHelper
	 */
	private $migration_helper;

	/**
	 * @var WPMDBRestAPIServer
	 */
	private $rest_API_server;

	/**
	 * @var Helper
	 */
	private $http_helper;

	/**
	 * @var Notice
	 */
	private $notice;

	/**
	 * @var ProfileManager
	 */
	private $profile_manager;

	/**
	 * @var UpgradeRoutinesManager
	 */
	private $upgrade_routines_manager;

	/**
	 * PluginManagerBase constructor.
	 *
	 * Free and Pro extend this class
	 *
	 * @param Settings               $settings
	 * @param Assets                 $assets
	 * @param Util                   $util
	 * @param Table                  $table
	 * @param Http                   $http
	 * @param Filesystem             $filesystem
	 * @param Multisite              $multisite
	 * @param Properties             $properties
	 * @param MigrationHelper        $migration_helper
	 * @param WPMDBRestAPIServer     $rest_API_server
	 * @param Helper                 $http_helper
	 * @param Notice                 $notice
	 * @param ProfileManager         $profile_manager
	 * @param UpgradeRoutinesManager $upgrade_routines_manager
	 */
	public function __construct(
		Settings $settings,
		Assets $assets,
		Util $util,
		Table $table,
		Http $http,
		Filesystem $filesystem,
		Multisite $multisite,
		Properties $properties,
		MigrationHelper $migration_helper,
		WPMDBRestAPIServer $rest_API_server,
		Helper $http_helper,
		Notice $notice,
		ProfileManager $profile_manager,
		UpgradeRoutinesManager $upgrade_routines_manager
	) {
		$this->props                    = $properties;
		$this->settings                 = $settings->get_settings();
		$this->assets                   = $assets;
		$this->util                     = $util;
		$this->tables                   = $table;
		$this->http                     = $http;
		$this->filesystem               = $filesystem;
		$this->multisite                = $multisite;
		$this->migration_helper         = $migration_helper;
		$this->rest_API_server          = $rest_API_server;
		$this->http_helper              = $http_helper;
		$this->notice                   = $notice;
		$this->profile_manager          = $profile_manager;
		$this->upgrade_routines_manager = $upgrade_routines_manager;
	}

	/**
	 * Register a bunch of action and hooks for the plugin initialization
	 */
	public function register() {
		// display a notice when either WP Migrate DB or WP Migrate DB Pro is automatically deactivated
		add_action( 'pre_current_active_plugins', array( $this, 'plugin_deactivated_notice' ) );
		// check if WP Engine is filtering the buffer and prevent it
		add_action( 'plugins_loaded', array( $this, 'maybe_disable_wp_engine_filtering' ) );

		//Remove 'Expect' header which some setups have issues with
		add_filter( 'http_request_args', array( $this->util, 'preempt_expect_header' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'maybe_schema_update' ) );

		// REST endpoints
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		if ( ! is_writable( Filesystem::get_upload_info( 'path' ) ) ) {
			add_filter( 'wpmdb_notification_strings', array( $this, 'template_uploads_not_writable' ) );
		}
	}

	public function template_uploads_not_writable( $templates ) {
		$reminder_notice_name = 'wpmdb_uploads_not_writable';
		$reminder_links       = $this->notice->check_notice( $reminder_notice_name, true, ( DAY_IN_SECONDS * 5 ) );

		// reminder notice
		if ( $reminder_links ) {
			$templates[ $reminder_notice_name ] = [
				'message' => sprintf(
					__(
						'<strong>Uploads directory not writable</strong> &mdash; the following directory is not writable: <code>%s</code>. Update the file permissions for this folder to enable backups and export migrations. <a href="https://deliciousbrains.com/wp-migrate-db-pro/doc/uploads-folder-permissions/" target="_blank">More information</a>.<br><br>',
						'wp-migrate-db'
					),
					Filesystem::get_upload_info( 'path' )
				),
				'link'    => $reminder_links,
				'id'      => $reminder_notice_name,
			];
		}

		return $templates;
	}

	public function register_rest_routes() {
		$this->rest_API_server->registerRestRoute( '/process-notice-link', [
			'methods'  => 'POST',
			'callback' => [ $this, 'ajax_process_notice_link' ],
		] );
	}

	/**
	 * Performs a schema update if required.
	 *
	 */
	public function maybe_schema_update() {
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		$schema_version = get_site_option( WPMDB_SCHEMA_VERSION_OPTION );
		$update_schema  = false;

		/*
		 * Upgrade this option to a network wide option if the site has been upgraded
		 * from a regular WordPress installation to a multisite installation.
		 */
		if ( false === $schema_version && is_multisite() && is_network_admin() ) {
			$schema_version = get_option( WPMDB_SCHEMA_VERSION_OPTION );
			if ( false !== $schema_version ) {
				update_site_option( WPMDB_SCHEMA_VERSION_OPTION, $schema_version );
				delete_option( WPMDB_SCHEMA_VERSION_OPTION );
			}
		}

		do_action( 'wpmdb_before_schema_update', $schema_version );

		if ( false === $schema_version ) {
			$schema_version = 0;
		}

		if ( $schema_version < 3.2 ) {
			$error_log = get_option( WPMDB_ERROR_LOG_OPTION );
			// skip multisite installations as we can't use add_site_option because it doesn't include an 'autoload' argument
			if ( false !== $error_log && false === is_multisite() ) {
				delete_option( WPMDB_ERROR_LOG_OPTION );
				add_option( WPMDB_ERROR_LOG_OPTION, $error_log, '', 'no' );
			}

			if ( isset( $this->settings['delay_between_requests'] ) ) {
				$delay_between_requests = (int) $this->settings['delay_between_requests'];

				if ( $delay_between_requests >= 1000 ) {
					$this->settings['delay_between_requests'] = $delay_between_requests / 1000;
					update_site_option( WPMDB_SETTINGS_OPTION, $this->settings );
				}
			}

			$update_schema  = true;
			$schema_version = 3.2;
		}

		if ( $schema_version < 3.6 ) {
			$this->update_profiles();

			$update_schema  = true;
			$schema_version = 3.6;
		}

		if ( true === $update_schema ) {
			update_site_option( WPMDB_SCHEMA_VERSION_OPTION, $schema_version );
		}

		//Since 2.6.0, this is the way to manage upgrade routines.
		$this->upgrade_routines_manager->perform_upgrade_routines();

		do_action( 'wpmdb_after_schema_update', $schema_version );
	}

	function plugin_deactivated_notice() {
		if ( false !== ( $deactivated_notice_id = get_transient( WPMDB_DEACTIVATED_NOTICE_ID_TRANSIENT ) ) ) {
			switch ( $deactivated_notice_id ) {
				case WPMDB_LITE_DEACTIVATED_FOR_PRO_ID:
					$message = __(
						"WP Migrate Lite and WP Migrate cannot both be active. We've automatically deactivated WP Migrate Lite.",
						'wp-migrate-db'
					);
					break;
				case WPMDB_DEACTIVATED_FOR_WPESM_ID:
					$message = __(
						"WP Engine Site Migration is powered by WP Migrate and shares some of the same functionality. In order to prevent conflicts between the two plugins, WP Migrate has been deactivated.",
						'wp-migrate-db'
					);
					break;
				case WPESM_DEACTIVATED_FOR_WPMDB_ID:
					$message = __(
						"WP Engine Site Migration is powered by WP Migrate and shares some of the same functionality. In order to prevent conflicts between the two plugins, WP Engine Site Migration has been deactivated.",
						'wp-migrate-db'
					);
					break;
				default:
					$message = __(
						"WP Migrate Lite and WP Migrate cannot both be active. We've automatically deactivated WP Migrate.",
						'wp-migrate-db'
					);
			}

			?>

			<div class="updated" style="border-left: 4px solid #ffba00;">
				<p><?php echo esc_html( $message ); ?></p>
			</div> <?php

			delete_transient( WPMDB_DEACTIVATED_NOTICE_ID_TRANSIENT );
		}
	}

	/**
	 * Supply inline JS data and nonces for enqueued scripts.
	 *
	 * @return void
	 */
	function admin_head_connection_info() {
		$nonces = apply_filters(
			'wpmdb_nonces',
			array(
				'migrate_table' => Util::create_nonce( 'migrate-table' ),
				'flush'         => Util::create_nonce( 'flush' ),
				'track_usage'   => Util::create_nonce( 'track-usage' ),
				'rest_nonce'    => Util::create_nonce( 'wp_rest' ),
			)
		);

		$site_details_extended           = $this->migration_helper->siteDetails();
		$site_details_extended['nonces'] = $nonces;

		$data = $site_details_extended;

		if ( Util::isPro() || Util::isWPE() ) {
			$data = apply_filters( 'wpmdb_data', $site_details_extended );
		}

		wp_localize_script( 'wp-migrate-db-pro-script-v2', 'wpmdb_data', $data );
	}

	/**
	 * When the "Use SSL for WP-admin and WP-login" option is checked in the
	 * WP Engine settings, the WP Engine must-use plugin buffers the output and
	 * does a find & replace for URLs. When we return PHP serialized data, it
	 * replaces http:// with https:// and corrupts the serialization.
	 * So here, we disable this filtering for our requests.
	 */
	public function maybe_disable_wp_engine_filtering() {
		// Detect if the must-use WP Engine plugin is running
		if ( ! defined( 'WPE_PLUGIN_BASE' ) ) {
			return;
		}

		// Make sure this is a WP Migrate DB Ajax request
		if ( ! Util::is_ajax() ) {
			return;
		}

		// Turn off WP Engine's output filtering
		if ( ! defined( 'WPE_NO_HTML_FILTER' ) ) {
			define( 'WPE_NO_HTML_FILTER', true );
		}
	}

	public function plugins_dir() {
		$path = untrailingslashit( $this->props->plugin_dir_path );

		return substr( $path, 0, strrpos( $path, DIRECTORY_SEPARATOR ) ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Handler for ajax request to process a link click in a notice, e.g. licence deactivated ... re-check.
	 *
	 * @return void
	 */
	public function ajax_process_notice_link() {
		$_POST = $this->http_helper->convert_json_body_to_post();

		$key_rules = array(
			'notice'   => 'string',
			'type'     => 'key',
			'reminder' => 'int',
		);

		$_POST = Sanitize::sanitize_data( $_POST, $key_rules, __METHOD__ );

		if ( false === $_POST ) {
			$this->http->end_ajax(
				new WP_Error(
					'wpmdb-process-link-ajax-failed',
					__( 'AJAX request failed', 'wp-migrate-db' )
				)
			);
		}

		global $current_user;
		$key   = WPMDB_OPTION_PREFIX . $_POST['type'] . '_' . $_POST['notice'];
		$value = true;

		if ( 'reminder' === $_POST['type'] && isset( $_POST['reminder'] ) ) {
			$value = time() + ( is_numeric( $_POST['reminder'] ) ? $_POST['reminder'] : 604800 );
		}

		update_user_meta( $current_user->ID, $key, $value );

		$this->http->end_ajax( 'notice saved' );
	}

	public function get_plugin_title() {
		return __( 'WP Migrate', 'wp-migrate-db' );
	}

	/**
	 * Runs on schema update events, responsible for updating media file profiles.
	 */
	private function update_profiles() {
		foreach ( [ WPMDB_SAVED_PROFILES_OPTION, WPMDB_RECENT_MIGRATIONS_OPTION ] as $profile_type ) {
			$profiles = $profile_type === WPMDB_SAVED_PROFILES_OPTION
				? $this->assets->get_saved_migration_profiles()
				: $this->assets->get_recent_migrations( get_site_option( $profile_type ) );

			foreach ( $profiles as $profile ) {
				$loaded_profile = $this->profile_manager->get_profile_by_id(
					$profile['id'],
					$profile_type === WPMDB_RECENT_MIGRATIONS_OPTION ? 'unsaved' : $profile_type
				);

				if ( is_wp_error( $loaded_profile ) ) {
					continue;
				}

				$profile_data = json_decode( $loaded_profile['value'] );

				//Enable database migration by default for pre 2.3 profiles
				if ( ! property_exists( $profile_data->current_migration, 'databaseEnabled' ) ) {
					$profile_data->current_migration->databaseEnabled = true;
				}

				if (
					property_exists( $profile_data, 'media_files' ) &&
					! property_exists( $profile_data->media_files, 'last_migration' )
				) {
					$profile_data->media_files->last_migration = property_exists( $profile_data->media_files, 'date' )
						? $profile_data->media_files->date
						: null;
				}

				if ( property_exists( $profile_data, 'theme_plugin_files' ) ) {
					if ( ! property_exists( $profile_data->theme_plugin_files, 'themes_option' ) ) {
						$profile_data->theme_plugin_files->themes_option = $profile_data->theme_plugin_files->themes_selected ? 'selected' : 'all';
					}
					if ( ! property_exists( $profile_data->theme_plugin_files, 'plugins_option' ) ) {
						$profile_data->theme_plugin_files->plugins_option = $profile_data->theme_plugin_files->plugins_selected ? 'selected' : 'all';
					}
					if ( ! property_exists( $profile_data->theme_plugin_files, 'themes_excluded' ) ) {
						$profile_data->theme_plugin_files->themes_excluded = [];
					}
					if ( ! property_exists( $profile_data->theme_plugin_files, 'plugins_excluded' ) ) {
						$profile_data->theme_plugin_files->plugins_excluded = [];
					}
					if ( ! property_exists( $profile_data->theme_plugin_files, 'plugins_excludes' ) ) {
						$profile_data->theme_plugin_files->plugins_excludes = property_exists(
							$profile_data->theme_plugin_files,
							'excludes'
						) ? $profile_data->theme_plugin_files->excludes : '';
					}
					if ( ! property_exists( $profile_data->theme_plugin_files, 'themes_excludes' ) ) {
						$profile_data->theme_plugin_files->themes_excludes = property_exists(
							$profile_data->theme_plugin_files,
							'excludes'
						) ? $profile_data->theme_plugin_files->excludes : '';
					}

					//updates for others and muplugins added 2.3.4
					if ( ! property_exists( $profile_data->theme_plugin_files, 'other_files' ) ) {
						$profile_data->theme_plugin_files->other_files     = [ 'enabled' => false ];
						$profile_data->theme_plugin_files->others_option   = 'selected';
						$profile_data->theme_plugin_files->others_selected = [];
						$profile_data->theme_plugin_files->others_excludes = '';
					}
					if ( ! property_exists( $profile_data->theme_plugin_files, 'muplugin_files' ) ) {
						$profile_data->theme_plugin_files->muplugin_files     = [ 'enabled' => false ];
						$profile_data->theme_plugin_files->muplugins_option   = 'selected';
						$profile_data->theme_plugin_files->muplugins_selected = [];
						$profile_data->theme_plugin_files->muplugins_excludes = '';
					}
				}
				//gonna need to update the profiles

				$saved_profiles                            = get_site_option( $profile_type );
				$saved_profiles[ $profile['id'] ]['value'] = json_encode( $profile_data );
				update_site_option( $profile_type, $saved_profiles );
			}
		}
	}

	/**
	 * Filter admin footer text for Migrate pages
	 *
	 * @param string $text
	 * @param string $product_link
	 * @param string $wpe_link
	 *
	 * @return string
	 * @handles admin_footer_text
	 **/
	public function generate_admin_footer_text( $text, $product_link, $wpe_link ) {
		if ( empty( $product_link ) || empty( $wpe_link ) ) {
			return $text;
		}

		// translators: %1$s is a link to WP Migrate's website, and %2$s is a link to WP Engine's website.
		return sprintf( __( '%1$s is developed and maintained by %2$s.', 'wp-migrate-db' ), $product_link, $wpe_link );
	}

	/**
	 * Generate Delicious Brains site URL with correct UTM tags.
	 *
	 * @param string $path
	 * @param array  $args
	 * @param string $hash
	 *
	 * @return string
	 */
	public static function delicious_brains_url( $path, $args = array(), $hash = '' ) {
		$args = wp_parse_args( $args, array(
			'utm_medium' => 'insideplugin',
		) );
		$args = array_map( 'urlencode', $args );
		$url  = trailingslashit( self::DBRAINS_URL ) . ltrim( $path, '/' );
		$url  = add_query_arg( $args, $url );
		if ( $hash ) {
			$url .= '#' . $hash;
		}

		return $url;
	}

	/**
	 * Generate WP Engine site URL with correct UTM tags.
	 *
	 * @param string $path
	 * @param array  $args
	 * @param string $hash
	 *
	 * @return string
	 */
	public static function wpe_url( $path = '', $args = array(), $hash = '' ) {
		$args = wp_parse_args( $args, [
			'utm_medium'   => 'referral',
			'utm_campaign' => 'bx_prod_referral',
		] );
		$args = array_map( 'urlencode', $args );
		$url  = trailingslashit( self::WPE_URL ) . ltrim( $path, '/' );
		$url  = add_query_arg( $args, $url );

		if ( $hash ) {
			$url .= '#' . $hash;
		}

		return $url;
	}
}
