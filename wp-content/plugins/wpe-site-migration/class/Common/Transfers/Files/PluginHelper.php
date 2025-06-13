<?php

namespace DeliciousBrains\WPMDB\Common\Transfers\Files;

use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\RemotePost;
use DeliciousBrains\WPMDB\Common\Http\Scramble;
use DeliciousBrains\WPMDB\Common\Migration\MigrationHelper;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\MigrationState\StateDataContainer;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Util\ZipAndEncode;
use DeliciousBrains\WPMDB\Common\Queue\Manager;
use DeliciousBrains\WPMDB\Data\Stage;
use Exception;
use WP_Error;

/**
 * @phpstan-import-type StageName from Stage
 */
class PluginHelper {
	public $filesystem;

	/**
	 * @var Http
	 */
	protected $http;

	/**
	 * @var ErrorLog
	 */
	protected $error_log;

	/**
	 * @var Helper
	 */
	protected $http_helper;

	/**
	 * @var RemotePost
	 */
	protected $remote_post;

	/**
	 * @var Settings
	 */
	protected $settings;

	/**
	 * @var MigrationStateManager
	 */
	protected $migration_state_manager;

	/**
	 * @var Scramble
	 */
	protected $scramble;

	/**
	 * @var FileProcessor
	 */
	protected $file_processor;

	/**
	 * @var Util
	 */
	protected $transfer_util;

	/**
	 * @var Manager
	 */
	protected $queue_manager;

	/**
	 * @var Properties
	 */
	protected $properties;

	/**
	 * @var Manager
	 */
	protected $manager;

	/**
	 * @var StateDataContainer
	 */
	protected $state_data_container;

	public function __construct(
		Filesystem $filesystem,
		Properties $properties,
		Http $http,
		Helper $http_helper,
		Settings $settings,
		MigrationStateManager $migration_state_manager,
		Scramble $scramble,
		FileProcessor $file_processor,
		Util $transfer_util,
		Manager $queue_manager,
		Manager $manager,
		StateDataContainer $state_data_container
	) {
		$this->filesystem              = $filesystem;
		$this->http                    = $http;
		$this->http_helper             = $http_helper;
		$this->settings                = $settings->get_settings();
		$this->migration_state_manager = $migration_state_manager;
		$this->scramble                = $scramble;
		$this->file_processor          = $file_processor;
		$this->transfer_util           = $transfer_util;
		$this->properties              = $properties;
		$this->queue_manager           = $queue_manager;
		$this->manager                 = $manager;
		$this->state_data_container    = $state_data_container;
	}

	/**
	 * @param StageName $stage
	 *
	 * @return void
	 */
	public function respond_to_get_remote_folders( $stage ) {
		MigrationHelper::set_is_remote();

		$key_rules = array(
			'action'             => 'key',
			'intent'             => 'key',
			'migration_state_id' => 'key',
			'folders'            => 'json',
			'excludes'           => 'json',
			'stage'              => 'string',
			'sig'                => 'string',
			'date'               => 'string',
			'timezone'           => 'string',
		);

		$_POST['folders']  = stripslashes( $_POST['folders'] );
		$_POST['excludes'] = stripslashes( $_POST['excludes'] );

		$state_data = Persistence::setRemotePostData( $key_rules, __METHOD__ );

		// Check for CLI migration and skip enabling recursive scanner if necessary.
		Util::enable_scandir_bottleneck();

		$filtered_post = $this->http_helper->filter_post_elements(
			$state_data,
			array(
				'action',
				'intent',
				'migration_state_id',
				'folders',
				'excludes',
				'stage',
			)
		);
		$verification  = $this->http_helper->verify_signature( $filtered_post, $this->settings['key'] );

		if ( ! $verification ) {
			$this->http->end_ajax(
				new WP_Error( 'wpmdbtpf_invalid_post_data', __( 'Could not validate $_POST data.' ) . ' (#100tp)' )
			);

			return;
		}

		MigrationHelper::set_current_migration_id( $filtered_post['migration_state_id'] );

		$date     = isset( $_POST['date'] ) ? $state_data['date'] : null;
		$timezone = ! empty( $_POST['timezone'] ) ? $state_data['timezone'] : 'UTC';

		$folders = json_decode( $state_data['folders'], true );

		if ( Stage::MEDIA_FILES === $stage ) {
			$folders = apply_filters( 'wpmdb_mf_remote_uploads_folder', $folders, $state_data );
		}
		$items = $folders;

		if ( $stage === Stage::MEDIA_FILES && isset( $folders[0] ) ) {
			$items = $this->get_top_level_items( $folders[0] );
		}

		$files = $this->file_processor->get_local_files(
			$items,
			json_decode( $state_data['excludes'], true ),
			$stage,
			$date,
			$timezone,
			'pull'
		);

		if ( is_wp_error( $files ) ) {
			$this->http->end_ajax( $files );

			return;
		}

		$files = ZipAndEncode::encode( json_encode( $files ) );

		$this->http->end_ajax( $files );
	}

	public function get_top_level_items( $dir ) {
		$file_data = $this->filesystem->scandir( $dir );

		$items = [];

		if ( ! $file_data ) {
			return false;
		}

		foreach ( $file_data as $item ) {
			$items[] = $item['absolute_path'];
		}

		return $items;
	}

	public function cleanup_transfer_migration( $stage ) {
		$this->remove_tmp_files( $stage );
	}

	/**
	 * Removes temporary files and directories.
	 *
	 * @param StageName $stage
	 */
	public function remove_tmp_files( $stage, $env = 'local' ) {
		$file_stages = [ Stage::THEMES, Stage::PLUGINS, Stage::MUPLUGINS, Stage::OTHERS, Stage::CORE, Stage::ROOT ];
		if ( in_array( $stage, $file_stages ) ) {
			array_map( [ $this->transfer_util, 'remove_tmp_folder'], $file_stages );
		}

		if ( $stage === Stage::MEDIA_FILES ) {
			$this->transfer_util->remove_tmp_folder( $stage );
		}

		$id = null;

		if ( $env === 'local' ) {
			$state_data = Persistence::getStateData();
			$id         = isset( $state_data['migration_state_id'] ) ? $state_data['migration_state_id'] : null;
		} else {
			$state_data = Persistence::getRemoteStateData();
			$id         = isset( $state_data['remote_state_id'] ) ? $state_data['remote_state_id'] : null;
		}

		if ( $id ) {
			$this->remove_chunk_data( $id, $env );
			$this->remove_folder_options( $id );
		}

		return;
	}

	public function remove_folder_options( $id ) {
		delete_site_option( WPMDB_FOLDER_TRANSFER_MEDIA_FILES_OPTION . $id );
		delete_site_option( WPMDB_FOLDER_TRANSFER_THEME_FILES_OPTION . $id );
		delete_site_option( WPMDB_FOLDER_TRANSFER_PLUGIN_FILES_OPTION . $id );
		delete_site_option( WPMDB_FOLDER_TRANSFER_MUPLUGIN_FILES_OPTION . $id );
		delete_site_option( WPMDB_FOLDER_TRANSFER_OTHER_FILES_OPTION . $id );
	}

	public function remove_chunk_data( $id, $env ) {
		if ( ! $id || $env !== 'local' ) {
			return;
		}

		$chunk_file = Chunker::get_chunk_path( $id );
		if ( $this->filesystem->file_exists( $chunk_file ) ) {
			$this->filesystem->unlink( $chunk_file );
		}

		$chunk_option_name = WPMDB_FILE_CHUNK_OPTION_PREFIX . $id;
		delete_site_option( $chunk_option_name );
	}
}
