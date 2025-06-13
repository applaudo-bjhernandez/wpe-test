<?php

namespace DeliciousBrains\WPMDB\Common\TPF;

use DeliciousBrains\WPMDB\Common\Debug;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\WPMDBRestAPIServer;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\Queue\Manager;
use DeliciousBrains\WPMDB\Common\Queue\QueueHelper;
use DeliciousBrains\WPMDB\Common\Transfers\Files\FileProcessor;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Common\Transfers\Files\TransferManager;
use DeliciousBrains\WPMDB\Common\Util\Util as CommonUtil;
use DeliciousBrains\WPMDB\Data\Stage;
use WP_Error;

/**
 * Class ThemePluginFilesLocal
 *
 * Handles local themes/plugins logic
 *
 * @phpstan-import-type StageName from Stage
 */
class ThemePluginFilesLocal {
	/**
	 * @var Util
	 */
	public $transfer_util;

	/**
	 * @var TransferManager
	 */
	public $transfer_manager;

	/**
	 * @var FileProcessor
	 */
	public $file_processor;

	/**
	 * @var Manager
	 */
	public $queueManager;

	/**
	 * @var \DeliciousBrains\WPMDB\Common\Util\Util
	 */
	public $util;

	/**
	 * @var MigrationStateManager
	 */
	public $migration_state_manager;

	/**
	 * @var Http
	 */
	public $http;

	/**
	 * @var TransferCheck
	 */
	private $check;

	/**
	 * @var Filesystem
	 */
	private $filesystem;

	/**
	 * @var WPMDBRestAPIServer
	 */
	private $rest_API_server;

	/**
	 * @var Helper
	 */
	private $http_helper;

	/**
	 * @var QueueHelper
	 */
	private $queue_helper;

	public function __construct(
		Util $util,
		\DeliciousBrains\WPMDB\Common\Util\Util $common_util,
		FileProcessor $file_processor,
		Manager $queue_manager,
		TransferManager $transfer_manager,
		MigrationStateManager $migration_state_manager,
		Http $http,
		Filesystem $filesystem,
		TransferCheck $check,
		WPMDBRestAPIServer $rest_API_server,
		Helper $http_helper,
		QueueHelper $queue_helper
	) {
		$this->util                    = $common_util;
		$this->queueManager            = $queue_manager;
		$this->transfer_util           = $util;
		$this->file_processor          = $file_processor;
		$this->transfer_manager        = $transfer_manager;
		$this->migration_state_manager = $migration_state_manager;
		$this->http                    = $http;
		$this->check                   = $check;
		$this->filesystem              = $filesystem;
		$this->rest_API_server         = $rest_API_server;
		$this->http_helper             = $http_helper;
		$this->queue_helper            = $queue_helper;
	}

	public function register() {
		// Register transfer manager actions
		$this->transfer_manager->register();

		add_filter( 'wpmdb_enqueue_stage', array( $this, 'filter_enqueue_stage' ), 10, 2 );
		add_filter( 'wpmdb_initiate_migration', array( $this->check, 'transfer_check' ) );
	}

	/**
	 * Initiate a file migration.
	 *
	 * @TODO Break this up into smaller, testable functions
	 *
	 * @param array|false $state_data
	 *
	 * @return array|WP_Error
	 */
	public function initiate_file_migration( $state_data = false ) {
		Debug::log( __METHOD__ );
		$this->util->set_time_limit();

		$key_rules = array(
			'action'             => 'key',
			'stage'              => 'string',
			'migration_state_id' => 'key',
			'folders'            => 'json_array',
			'theme_folders'      => 'json_array',
			'themes_option'      => 'string',
			'themes_excludes'    => 'json',
			'plugin_folders'     => 'json_array',
			'plugins_option'     => 'string',
			'plugins_excludes'   => 'json',
			'muplugin_folders'   => 'json_array',
			'muplugins_option'   => 'string',
			'muplugins_excludes' => 'json',
			'other_folders'      => 'json_array',
			'others_option'      => 'string',
			'others_excludes'    => 'json',
			'core_folders'       => 'json_array',
			'core_option'        => 'string',
			'core_excludes'      => 'json',
			'root_folders'       => 'json_array',
			'root_option'        => 'string',
			'root_excludes'      => 'json',
			'nonce'              => 'key',
		);

		$state_data = Persistence::setPostData( $key_rules, __METHOD__, WPMDB_MIGRATION_STATE_OPTION, $state_data );

		if ( is_wp_error( $state_data ) ) {
			return $state_data;
		}

		$excludes = isset( $state_data[ $state_data['stage'] . '_excludes' ] )
			? CommonUtil::trim_excludes( $state_data[ $state_data['stage'] . '_excludes' ] )
			: [];

		$split_excludes = \DeliciousBrains\WPMDB\Common\Util\Util::split_excludes( $excludes );

		//Cleanup partial chunk files.
		$this->transfer_util->cleanup_temp_chunks();

		//State data populated
		$files = $state_data['folders'];

		if ( is_wp_error( $files ) ) {
			return $files;
		}

		if ( ! is_array( $files ) ) {
			return $this->transfer_util->log_and_return_error(
				__( 'Invalid folder list supplied (invalid array)', 'wp-migrate-db' )
			);
		}

		// @TODO this needs to be implemented for remotes on a pull
		$verified_folders = $this->verify_files_for_migration( $files );

		Util::enable_scandir_bottleneck();

		if ( 'pull' === $state_data['intent'] ) {
			// Set up local meta data
			$file_list = $this->transfer_util->get_remote_files(
				$files,
				'wpmdbtp_respond_to_get_remote_' . $state_data['stage'],
				$split_excludes
			);
		} else {
			// Push = get local files
			$file_list = $this->file_processor->get_local_files(
				$verified_folders,
				$split_excludes,
				$state_data['stage'],
				null,
				null,
				'push'
			);
		}

		if ( is_wp_error( $file_list ) ) {
			return $file_list;
		}

		$full_site_export = isset( $state_data['full_site_export'] ) ? $state_data['full_site_export'] : false;
		$queue_status     = $this->queue_helper->populate_queue(
			$file_list,
			$state_data['intent'],
			$state_data['stage'],
			$state_data['migration_state_id'],
			$full_site_export
		);

		if ( is_wp_error( $queue_status ) ) {
			return $queue_status;
		}

		set_site_transient( WPMDB_QUEUE_STATUS_OPTION, $queue_status );

		if ( isset( $file_list['meta']['scan_completed'] ) ) {
			if ( true === $file_list['meta']['scan_completed'] ) {
				return [ 'queue_status' => $queue_status ];
			}

			return [
				'recursive_queue' => true,
				'items_count'     => $queue_status['total'],
				'queue_status'    => $queue_status,
			];
		}

		return [ 'queue_status' => $queue_status ];
	}

	/**
	 * Filters the supplied file list, removing any files that don't exist.
	 *
	 * @param string[] $files The array of files to check.
	 *
	 * @return string[] The same array with any non-existent files removed.
	 */
	public function verify_files_for_migration( $files ) {
		Debug::log( __FUNCTION__ . ': files:-' );
		Debug::log( $files );
		$paths = [];

		foreach ( $files as $file ) {
			if ( $this->filesystem->file_exists( $file ) ) {
				$paths[] = $file;
			}
		}

		return $paths;
	}

	/**
	 * Handle enqueue_stage filter to enqueue all files to the queue and return their total bytes.
	 *
	 * @param array|WP_Error $progress
	 * @param StageName      $stage
	 *
	 * @return array|WP_Error
	 * @handles wpmdb_enqueue_stage
	 */
	public function filter_enqueue_stage( $progress, $stage ) {
		if ( ! in_array(
			$stage,
			[
				Stage::THEME_FILES,
				Stage::PLUGIN_FILES,
				Stage::MUPLUGIN_FILES,
				Stage::OTHER_FILES,
				Stage::CORE_FILES,
				Stage::ROOT_FILES,
			]
		) ) {
			return $progress;
		}

		if ( is_wp_error( $progress ) ) {
			return $progress;
		}

		Debug::log( __METHOD__ . ': ' . $stage );

		//Remove any existing temporary folders
		$this->transfer_util->cleanup_existing_temp_folders( $stage );

		$state_data = $this->get_state_data( $stage );

		// Not much we can do about this.
		if ( is_wp_error( $state_data ) ) {
			return $state_data;
		}

		$result = $this->initiate_file_migration( $state_data );

		// Not much we can do about this.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$progress['initialized_bytes'] = ! empty( $result['queue_status']['size'] ) ? $result['queue_status']['size'] : 0;
		$progress['complete']          = ! isset( $result['recursive_queue'] );

		return $progress;
	}

	/**
	 * Get TPF state data.
	 *
	 * @param StageName $stage
	 *
	 * @return array|WP_Error
	 */
	public function get_state_data( $stage ) {
		// We need multiple entries from options, so grab them all.
		$profile = Persistence::getMigrationOptions();

		if ( is_wp_error( $profile ) ) {
			return $profile;
		}

		if ( empty( $profile['current_migration'] ) ) {
			return $this->transfer_util->log_and_return_error(
				sprintf( __( 'Missing current migration details.', 'wp-migrate-db' ), $stage )
			);
		}

		if ( empty( $profile['theme_plugin_files'] ) ) {
			return $this->transfer_util->log_and_return_error(
				sprintf( __( 'Missing theme and plugin details.', 'wp-migrate-db' ), $stage )
			);
		}

		$remote = empty( $profile['remote_site'] ) ? [] : $profile['remote_site'];

		if ( in_array( $profile['action'], [ 'push', 'pull' ] ) && empty( $remote ) ) {
			return $this->transfer_util->log_and_return_error(
				sprintf( __( 'Missing remote site details.', 'wp-migrate-db' ), $stage )
			);
		}

		$state_data = [
			'action'             => $profile['action'],
			'migration_state_id' => $profile['current_migration']['migration_id'],
		];

		switch ( $stage ) {
			case Stage::THEME_FILES:
				$state_data['stage'] = Stage::THEMES;
				$files_to_migrate    = $this->prepare_files_to_migrate( $profile, 'themes' );

				if ( is_wp_error( $files_to_migrate ) ) {
					return $files_to_migrate;
				}

				$state_data['theme_folders'] = json_encode( $files_to_migrate );
				$state_data['folders']       = $state_data['theme_folders'];
				$state_data['themes_option'] = isset( $profile['theme_plugin_files']['themes_option'] )
					? $profile['theme_plugin_files']['themes_option']
					: '';
				if ( isset( $profile['theme_plugin_files']['themes_excludes'] ) ) {
					$state_data['themes_excludes'] = json_encode( $profile['theme_plugin_files']['themes_excludes'] );
				}
				break;
			case Stage::PLUGIN_FILES:
				$state_data['stage'] = Stage::PLUGINS;
				$files_to_migrate    = $this->prepare_files_to_migrate( $profile, 'plugins' );

				if ( is_wp_error( $files_to_migrate ) ) {
					return $files_to_migrate;
				}

				$state_data['plugin_folders'] = json_encode( $files_to_migrate );
				$state_data['folders']        = $state_data['plugin_folders'];
				$state_data['plugins_option'] = isset( $profile['theme_plugin_files']['plugins_option'] )
					? $profile['theme_plugin_files']['plugins_option']
					: '';
				if ( isset( $profile['theme_plugin_files']['plugins_excludes'] ) ) {
					$state_data['plugins_excludes'] = json_encode( $profile['theme_plugin_files']['plugins_excludes'] );
				}
				break;
			case Stage::MUPLUGIN_FILES:
				$state_data['stage'] = Stage::MUPLUGINS;
				$files_to_migrate    = $this->prepare_files_to_migrate( $profile, 'muplugins' );

				if ( is_wp_error( $files_to_migrate ) ) {
					return $files_to_migrate;
				}

				$state_data['muplugin_folders'] = json_encode( $files_to_migrate );
				$state_data['folders']          = $state_data['muplugin_folders'];
				$state_data['muplugins_option'] = isset( $profile['theme_plugin_files']['muplugins_option'] )
					? $profile['theme_plugin_files']['muplugins_option']
					: '';
				if ( isset( $profile['theme_plugin_files']['muplugins_excludes'] ) ) {
					$state_data['muplugins_excludes'] = json_encode( $profile['theme_plugin_files']['muplugins_excludes'] );
				}
				break;
			case Stage::OTHER_FILES:
				$state_data['stage'] = Stage::OTHERS;
				$files_to_migrate    = $this->prepare_files_to_migrate( $profile, 'others' );

				if ( is_wp_error( $files_to_migrate ) ) {
					return $files_to_migrate;
				}

				$state_data['other_folders'] = json_encode( $files_to_migrate );
				$state_data['folders']       = $state_data['other_folders'];
				$state_data['others_option'] = isset( $profile['theme_plugin_files']['others_option'] )
					? $profile['theme_plugin_files']['others_option']
					: '';
				if ( isset( $profile['theme_plugin_files']['others_excludes'] ) ) {
					$state_data['others_excludes'] = json_encode( $profile['theme_plugin_files']['others_excludes'] );
				}
				break;
			case Stage::CORE_FILES:
				$state_data['stage'] = Stage::CORE;
				$files_to_migrate    = $this->prepare_files_to_migrate( $profile, 'core' );

				if ( is_wp_error( $files_to_migrate ) ) {
					return $files_to_migrate;
				}

				$state_data['core_folders'] = json_encode( $files_to_migrate );
				$state_data['folders']      = $state_data['core_folders'];
				$state_data['core_option']  = isset( $profile['theme_plugin_files']['core_option'] )
					? $profile['theme_plugin_files']['core_option']
					: '';
				if ( isset( $profile['theme_plugin_files']['core_excludes'] ) ) {
					$state_data['core_excludes'] = json_encode( $profile['theme_plugin_files']['core_excludes'] );
				}
				break;
			case Stage::ROOT_FILES:
				$state_data['stage'] = Stage::ROOT;
				$files_to_migrate    = $this->prepare_files_to_migrate( $profile, 'root' );

				if ( is_wp_error( $files_to_migrate ) ) {
					return $files_to_migrate;
				}

				$state_data['root_folders'] = json_encode( $files_to_migrate );
				$state_data['folders']      = $state_data['root_folders'];
				$state_data['root_option']  = isset( $profile['theme_plugin_files']['root_option'] )
					? $profile['theme_plugin_files']['root_option']
					: '';
				if ( isset( $profile['theme_plugin_files']['root_excludes'] ) ) {
					$state_data['root_excludes'] = json_encode( $profile['theme_plugin_files']['root_excludes'] );
				}
				break;
			default:
				return $this->transfer_util->log_and_return_error(
					sprintf( __( 'Invalid stage "%s" supplied to file transfer initialization.', 'wp-migrate-db' ),
						$stage )
				);
		}

		return $state_data;
	}

	/**
	 * Prepare files to migrate for the stage
	 *
	 * @param array  $profile
	 * @param string $stage
	 *
	 * @return array|WP_Error
	 **/
	private function prepare_files_to_migrate( $profile, $stage ) {
		Debug::log( __FUNCTION__ . ': ' . $stage );
		switch ( $stage ) {
			case 'plugins':
				$stage_files_to_migrate = $this->get_plugins_to_migrate( $profile );
				break;
			case 'themes':
				$stage_files_to_migrate = $this->get_themes_to_migrate( $profile );
				break;
			default:
				$stage_files_to_migrate = $this->get_stage_files_to_migrate( $profile, $stage );
		}

		return $this->filter_items_to_migrate(
			$stage_files_to_migrate,
			$profile['theme_plugin_files']["{$stage}_excludes"],
			$stage
		);
	}

	/**
	 * Get files to migrate for the stage
	 *
	 * @param array  $profile
	 * @param string $stage
	 *
	 * @return array|WP_Error
	 **/
	public function get_stage_files_to_migrate( $profile, $stage ) {
		$files            = $this->get_stage_files_from_profile( $profile, $stage );
		$files_option     = isset( $profile['theme_plugin_files']["{$stage}_option"] )
			? $profile['theme_plugin_files']["{$stage}_option"]
			: '';
		$files_selected   = isset( $profile['theme_plugin_files']["{$stage}_selected"] )
			? $profile['theme_plugin_files']["{$stage}_selected"]
			: [];
		$files_to_migrate = [];
		if ( 'all' === $files_selected || 'all' === $files_option ) {
			$file_paths = array_map(
				function ( $file ) {
					return $file[0]['path'];
				},
				$files
			);

			return array_values( $file_paths );
		}

		if ( ! is_array( $files_selected ) ) {
			$files_selected = explode( ',', $files_selected );
		}

		$file_slugs = [];

		// Get things into a format we can work with.
		foreach ( $files as $file ) {
			$file_slugs[ $file[0]['path'] ] = $file;
		}
		$base_dir = CommonUtil::get_stage_base_dir( $stage );
		foreach ( $files_selected as $file ) {
			if ( strpos( $file, $base_dir ) === false ) {
				$file = $this->get_path_from_name( $files, $file );
			}
			$file = trim( $file );

			if ( ! $file ) {
				continue;
			}

			if ( ! isset( $file_slugs[ $file ] ) ) {
				$message = sprintf( __( 'File not found on source server: %s', 'wp-migrate-db' ), $file );

				return new WP_Error( 'wpmdbpro_theme_plugin_files_error', $message );
			}

			$files_to_migrate[] = $file_slugs[ $file ][0]['path'];
		}

		return $files_to_migrate;
	}

	/**
	 * Gets the themes to migrate.
	 *
	 * @param array $profile The current migration profile.
	 *
	 * @return array|WP_Error
	 */
	public function get_themes_to_migrate( $profile ) {
		$themes            = $this->get_stage_files_from_profile( $profile, 'themes' );
		$themes_option     = isset( $profile['theme_plugin_files']['themes_option'] )
			? $profile['theme_plugin_files']['themes_option']
			: null;
		$themes_selected   = isset( $profile['theme_plugin_files']['themes_selected'] )
			? $profile['theme_plugin_files']['themes_selected']
			: null;
		$themes_excluded   = isset( $profile['theme_plugin_files']['themes_excluded'] )
			? $profile['theme_plugin_files']['themes_excluded']
			: null;
		$themes_to_migrate = [];

		if ( 'new_updated' === $themes_option ) {
			return $this->get_new_updated_themes( $themes, $profile );
		}

		if ( 'all' === $themes_selected || 'all' === $themes_option ) {
			$theme_paths = array_map( function ( $theme ) {
				return $theme[0]['path'];
			}, $themes );

			return array_values( array_unique( $theme_paths ) );
		}

		if ( 'active' === $themes_option ) {
			$active_themes = [];
			foreach ( $themes as $theme ) {
				if ( $theme[0]['active'] ) {
					$active_themes[] = $theme[0]['path'];
				}
			}

			return array_values( array_unique( $active_themes ) );
		}

		if ( 'except' === $themes_option ) {
			$filtered_excluded = [];
			foreach ( $themes as $theme ) {
				if ( in_array( $theme[0]['path'], $themes_excluded ) ) {
					continue;
				}
				$filtered_excluded[] = $theme[0]['path'];
			}

			return array_values(  array_unique( $filtered_excluded ) );
		}

		if ( ! is_array( $themes_selected ) ) {
			$themes_selected = explode( ',', $themes_selected );
		}

		$valid_paths = [];
		foreach ( $themes as $theme ) {
			$valid_paths[ $theme[0]['path'] ] = true;
		}

		foreach ( $themes_selected as $selected_theme_path ) {
			$selected_theme_path = trim( $selected_theme_path );

			if ( ! $selected_theme_path ) {
				continue;
			}

			if ( ! isset( $valid_paths[ $selected_theme_path ] ) ) {
				$message = sprintf( __( 'Theme not found on source server: %s', 'wp-migrate-db' ), $selected_theme_path );
				return new WP_Error( 'wpmdbpro_theme_plugin_files_error', $message );
			}

			$themes_to_migrate[] = $selected_theme_path;
		}

		return array_unique( $themes_to_migrate );
	}

	/**
	 * Gets the plugins to migrate.
	 *
	 * @param array $profile The current migration profile.
	 *
	 * @return array|WP_Error
	 */
	public function get_plugins_to_migrate( $profile ) {
		$plugins            = $this->get_stage_files_from_profile( $profile, 'plugins' );
		$plugins_option     = isset( $profile['theme_plugin_files']['plugins_option'] )
			? $profile['theme_plugin_files']['plugins_option']
			: null;
		$plugins_selected   = isset( $profile['theme_plugin_files']['plugins_selected'] )
			? $profile['theme_plugin_files']['plugins_selected']
			: null;
		$plugins_excluded   = isset( $profile['theme_plugin_files']['plugins_excluded'] )
			? $profile['theme_plugin_files']['plugins_excluded']
			: null;
		$plugins_to_migrate = [];

		$plugins = Util::filter_excluded_plugins( $plugins );

		if ( 'new_updated' === $plugins_option ) {
			return $this->get_new_updated_plugins( $plugins, $profile );
		}

		if ( 'all' === $plugins_selected || 'all' === $plugins_option ) {
			$plugin_paths = array_map( function ( $plugin ) {
				return $plugin[0]['path'];
			}, $plugins );

			return array_values( $plugin_paths );
		}

		if ( 'active' === $plugins_option ) {
			$active_plugins = [];
			foreach ( $plugins as $plugin ) {
				if ( $plugin[0]['active'] ) {
					$active_plugins[] = $plugin[0]['path'];
				}
			}

			return array_values( $active_plugins );
		}

		if ( 'except' === $plugins_option ) {
			$filtered_excluded = [];
			foreach ( $plugins as $plugin ) {
				if ( in_array( $plugin[0]['path'], $plugins_excluded ) ) {
					continue;
				}
				$filtered_excluded[] = $plugin[0]['path'];
			}

			return array_values( $filtered_excluded );
		}

		if ( ! is_array( $plugins_selected ) ) {
			$plugins_selected = explode( ',', $plugins_selected );
		} else {
			$plugins_selected = array_map( function ( $plugin ) {
				return wp_basename( $plugin, '.php' );
			}, $plugins_selected );
		}

		$plugin_slugs = [];

		// Get things into a format we can work with.
		foreach ( $plugins as $key => $value ) {
			$slug                  = preg_replace( '/\/(.*)\.php|\.php/i', '', $key );
			$plugin_slugs[ $slug ] = $value;
		}

		foreach ( $plugins_selected as $plugin ) {
			$plugin = trim( $plugin );

			if ( ! $plugin ) {
				continue;
			}

			if ( ! isset( $plugin_slugs[ $plugin ] ) ) {
				$message = sprintf( __( 'Plugin not found on source server: %s', 'wp-migrate-db' ), $plugin );

				return new WP_Error( 'wpmdbpro_theme_plugin_files_error', $message );
			}

			$plugins_to_migrate[] = $plugin_slugs[ $plugin ][0]['path'];
		}

		return $plugins_to_migrate;
	}

	/**
	 * Get new and updated plugins
	 * 
	 * @param array $source_plugins See get_new_updated_versioned_items for the format of this.
	 * @param array $profile
	 * 
	 * @return string[]  
	 */
	public function get_new_updated_plugins( $source_plugins, $profile ) {
		$destination_plugins = $this->get_destination_stage_files_from_profile( $profile, 'plugins' );

		return $this->get_new_updated_versioned_items( $source_plugins, $destination_plugins );
	}

	/**
	 * Get new and updated themes
	 * 
	 * @param array $source_themes See get_new_updated_versioned_items for the format of this.
	 * @param array $profile
	 * 
	 * @return string[]  
	 */
	public function get_new_updated_themes( $source_themes, $profile ) {
		$destination_themes = $this->get_destination_stage_files_from_profile( $profile, 'themes' );

		return $this->get_new_updated_versioned_items( $source_themes, $destination_themes );
	}

	/**
	 * Get new and updated themes or plugins.
	 *
	 * In the source and destination item arrays, the theme/plugin details is as
	 * it exists in the migration options setting under local/remote_site['site_details'][stage].
	 *
	 * Note that the migration options is referred to as a "profile" in some related code, but this
	 * information is not saved in the profile, it only exists in the migration options setting.
	 *
	 * [
	 *     0 => [
	 *         'name'    => string <The display name of the theme/plugin>
	 *         'active'  => boolean <Whether the theme/plugin is active>
	 *         'path'    => string <The full path to the theme/plugin's directory>
	 *         'version' => string <The version of the theme/plugin>
	 *     ]
	 * ]
	 * 
	 * @param array $source_items      An array of source themes or plugins indexed by the theme/plugin name/slug
	 *                                 with the value being the theme/plugin details.
	 * @param array $destination_items An array of destination themes or plugins indexed by the theme/plugin name/slug
	 *                                 with the value being the theme/plugin details.
	 * 
	 * @return string[]  
	 */
	public function get_new_updated_versioned_items( $source_items, $destination_items ) {
		$new_updated_files = [];
		foreach ( $source_items as $key => $source_file ) {
			if ( ! array_key_exists( $key, $destination_items ) ) {
				$new_updated_files[] = $source_file[0]['path'];
				continue;
			}
			$destination_file    = $destination_items[ $key ];
			$destination_version = $destination_file[0]['version'];
			$source_version      = $source_file[0]['version'];

			if ( $source_version === null || $source_version === '' || $destination_version === null || $destination_version === '' ) {
				$new_updated_files[] = $source_file[0]['path'];
				continue;
			}

			if ( version_compare( $source_version, $destination_version, '>' ) ) {
				$new_updated_files[] = $source_file[0]['path'];
			}
		}
		return $new_updated_files;
	}

	/**
	 * Get file path to be used for array key
	 *
	 * @param array  $files_array
	 * @param string $file_name
	 *
	 * @return string
	 */
	public function get_path_from_name( $files_array, $file_name ) {
		foreach ( $files_array as $key => $val ) {
			if ( $val[0]['name'] === $file_name || $val[0]['path'] === $file_name ) {
				return $val[0]['path'];
			}
		}

		return '';
	}

	/**
	 * Get source site from profile
	 *
	 * @param array $profile
	 *
	 * @return array
	 **/
	public static function get_migration_source_site( $profile ) {
		if ( isset( $profile['action'] ) && $profile['action'] === 'pull' ) {
			return $profile['remote_site'];
		}

		return $profile['local_site'];
	}

	/**
	 * Get destination site from profile
	 *
	 * @param array $profile
	 *
	 * @return array
	 **/
	public static function get_migration_destination_site( $profile ) {
		if ( isset( $profile['action'] ) && $profile['action'] === 'pull' ) {
			return $profile['local_site'];
		}

		return $profile['remote_site'];
	}

	/**
	 * Get files from profile for the stage
	 *
	 * @param array  $profile
	 * @param string $stage
	 *
	 * @return array
	 **/
	public function get_stage_files_from_profile( $profile, $stage ) {
		$source_site = self::get_migration_source_site( $profile );

		return $source_site['site_details'][ $stage ];
	}

	/**
	 * Get destination files from profile for the stage
	 *
	 * @param array  $profile
	 * @param string $stage
	 *
	 * @return array
	 **/
	public function get_destination_stage_files_from_profile( $profile, $stage ) {
		$destination_site = self::get_migration_destination_site( $profile );

		return $destination_site['site_details'][ $stage ];
	}

	/**
	 * Filters out empty directories
	 *
	 * @param array        $items
	 * @param array|string $excludes
	 * @param string       $stage
	 *
	 * @return array
	 */
	private function filter_items_to_migrate( $items, $excludes, $stage ) {
		Debug::log( __METHOD__ );
		Debug::log( __FUNCTION__ . ':>>> items:-' );
		Debug::log( $items );
		Debug::log( __FUNCTION__ . ': excludes:-' );
		Debug::log( $excludes );
		if ( ! is_array( $items ) ) {
			return $items;
		}

		$items = array_unique( $items );

		$excludes   = CommonUtil::split_excludes( $excludes );
		$stage_path = CommonUtil::get_stage_base_dir( $stage );

		Debug::log( __FUNCTION__ . ': split excludes:-' );
		Debug::log( $excludes );
		Debug::log( __FUNCTION__ . ': stage path:- "' . $stage_path . '"' );

		foreach ( $items as $key => $item ) {
			if (
				CommonUtil::is_excluded_file( $item, $excludes, $stage_path ) ||
				Util::is_empty_dir( $item, $excludes, $stage_path )
			) {
				Debug::log( __FUNCTION__ . ': excluding item:-' );
				Debug::log( $item );
				unset( $items[ $key ] );
			}
		}

		Debug::log( __FUNCTION__ . ':<<< items:-' );
		Debug::log( $items );

		return $items;
	}
}
