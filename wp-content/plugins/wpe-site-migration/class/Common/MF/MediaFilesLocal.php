<?php

namespace DeliciousBrains\WPMDB\Common\MF;

use DateTime;
use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\WPMDBRestAPIServer;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;
use DeliciousBrains\WPMDB\Common\Profile\ProfileManager;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Common\Queue\Manager;
use DeliciousBrains\WPMDB\Common\Transfers\Files\FileProcessor;
use DeliciousBrains\WPMDB\Common\Transfers\Files\PluginHelper;
use DeliciousBrains\WPMDB\Common\Queue\QueueHelper;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util as Files_Util;
use DeliciousBrains\WPMDB\Common\Transfers\Files\TransferManager;
use DeliciousBrains\WPMDB\Data\Stage;
use WP_Error;

/**
 * @phpstan-import-type StageName from Stage
 */
class MediaFilesLocal {

	/**
	 * @var Http
	 */
	private $http;

	/**
	 * @var Util
	 */
	private $util;

	/**
	 * @var Helper
	 */
	private $http_helper;

	/**
	 * @var WPMDBRestAPIServer
	 */
	private $rest_API_server;

	/**
	 * @var TransferManager
	 */
	private $transfer_manager;

	/**
	 * @var Files_Util
	 */
	private $transfer_util;

	/**
	 * @var FileProcessor
	 */
	private $file_processor;

	/**
	 * @var QueueHelper
	 */
	private $queue_helper;

	/**
	 * @var Manager
	 */
	private $queue_manager;

	/**
	 * @var PluginHelper
	 */
	private $plugin_helper;

	/**
	 * @var FormData
	 */
	private $form_data;

	/**
	 * @var ProfileManager
	 */
	private $profile_manager;

	public function __construct(
		FormData $form_data,
		Http $http,
		Util $util,
		Helper $http_helper,
		WPMDBRestAPIServer $rest_API_server,
		TransferManager $transfer_manager,
		Files_Util $transfer_util,
		FileProcessor $file_processor,
		QueueHelper $queue_helper,
		Manager $queue_manager,
		PluginHelper $plugin_helper,
		ProfileManager $profile_manager
	) {
		$this->http             = $http;
		$this->util             = $util;
		$this->http_helper      = $http_helper;
		$this->rest_API_server  = $rest_API_server;
		$this->transfer_manager = $transfer_manager;
		$this->transfer_util    = $transfer_util;
		$this->file_processor   = $file_processor;
		$this->queue_helper     = $queue_helper;
		$this->queue_manager    = $queue_manager;
		$this->plugin_helper    = $plugin_helper;
		$this->form_data        = $form_data;
		$this->profile_manager  = $profile_manager;
	}

	public function register() {
		// Register transfer manager actions
		$this->transfer_manager->register();

		add_filter( 'wpmdb_enqueue_stage', array( $this, 'filter_enqueue_stage' ), 10, 2 );
		add_action( 'wpmdb_migration_complete', array( $this, 'mf_migration_complete' ) );
		add_action( 'wpmdb_respond_to_push_cancellation', array( $this, 'remove_remote_tmp_files' ) );
		add_action( 'wpmdb_cancellation', array( $this, 'mf_migration_stopped_actions' ) );
		add_action( 'wpmdb_finalize_migration', array( $this, 'finalize_migration' ) );
		add_action(
			'wpmdb_finalize_key_rules',
			function ( $key_rules ) {
				$key_rules['profileID']   = 'int';

				return $key_rules;
			}
		);
	}

	/**
	 * Initiate a media files migration.
	 *
	 * @param array|false $state_data
	 *
	 * @return array|WP_Error
	 */
	public function initiate_media_file_migration( $state_data = false ) {
		$this->util->set_time_limit();

		$key_rules = array(
			'excludes'           => 'json',
			'migration_state_id' => 'key',
			'folder'             => 'string',
			'stage'              => 'string',
			'date'               => 'string',
			'timezone'           => 'string',
		);

		$state_data = Persistence::setPostData( $key_rules, __METHOD__, WPMDB_MIGRATION_STATE_OPTION, $state_data );

		if ( is_wp_error( $state_data ) ) {
			return $state_data;
		}

		$excludes = isset( $state_data['excludes'] ) ? trim( $state_data['excludes'], "\" \t\n\r\0\x0B" ) : [];

		if ( ! is_array( $excludes ) ) {
			// stripcslashes() makes the $excludes string double quoted so we can use preg_split().
			$excludes = preg_split( '/\r\n|\r|\n/', stripcslashes( $excludes ) );
		}

		$excludes[] = 'wp-migrate-db';
		$excludes   = apply_filters( 'wpmdb_mf_excludes', $excludes, $state_data );

		//Cleanup partial chunk files.
		$this->transfer_util->cleanup_temp_chunks();

		//Bottleneck files scanning
		Files_Util::enable_scandir_bottleneck();

		//State data populated
		$folder   = $state_data['folder'];
		$date     = isset( $state_data['date'] ) ? $state_data['date'] : null;
		$timezone = ! empty( $state_data['timezone'] ) ? $state_data['timezone'] : 'UTC';

		if ( empty( $folder ) ) {
			return $this->transfer_util->log_and_return_error( __( 'Invalid folder path supplied.', 'wp-migrate-db' ) );
		}

		if ( 'pull' === $state_data['intent'] ) {
			// Set up local meta data
			$folder    = apply_filters( 'wpmdb_mf_remote_uploads_source_folder', $folder, $state_data );
			$file_list = $this->transfer_util->get_remote_files(
				[ $folder ],
				'wpmdbmf_respond_to_get_remote_media',
				$excludes,
				$date,
				$timezone
			);
		} else {
			// Push = get local files
			$abs_path = Files_Util::get_wp_uploads_dir();
			$abs_path = apply_filters( 'wpmdb_mf_local_uploads_folder', $abs_path, $state_data );
			$items    = $this->plugin_helper->get_top_level_items( $abs_path );

			$file_list = $this->file_processor->get_local_files(
				$items,
				$excludes,
				$state_data['stage'],
				$date,
				$timezone,
				'push'
			);
		}

		if ( is_wp_error( $file_list ) ) {
			return $file_list;
		}

		$queue_status = $this->queue_helper->populate_queue(
			$file_list,
			$state_data['intent'],
			$state_data['stage'],
			$state_data['migration_state_id']
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

	public function mf_migration_complete() {
		$this->mf_migration_stopped_actions();
	}

	public function mf_migration_stopped_actions() {
		$stages = $this->form_data->getMigrationStages();

		if ( is_array( $stages ) && in_array( Stage::MEDIA_FILES, $stages, true ) ) {
			$this->plugin_helper->cleanup_transfer_migration( Stage::MEDIA_FILES );
		}
	}

	public function remove_remote_tmp_files() {
		$stages = $this->form_data->getMigrationStages();

		if ( is_array( $stages ) && in_array( Stage::MEDIA_FILES, $stages ) ) {
			$this->plugin_helper->remove_tmp_files( Stage::MEDIA_FILES, 'remote' );
		}
	}

	/**
	 * Runs on the finalize_migration hook
	 *
	 * @param array $state_data
	 * 
	 * @return void
	 * 
	 * @handles wpmdb_finalize_migration
	 */
	public function finalize_migration( $state_data ) {
		$this->update_profile( $state_data );
	}

	/**
	 * Runs on the finalize_migration hook and updates the profile
	 * with the last migration date.
	 *
	 * @param array $state_data  Array of state data from the migration.
	 *
	 * @return bool|mixed|void|WP_Error
	 */
	public function update_profile( $state_data ) {
		if ( ! isset( $state_data['profileID'] ) ) {
			return;
		}

		$profileID = $state_data['profileID'];

		$profile = $this->profile_manager->get_profile_by_id( $profileID );

		if ( is_wp_error( $profile ) ) {
			return $profile;
		}

		if ( ! is_array( $profile ) || ! array_key_exists( 'value', $profile ) ) {
			return;
		}
		$profile_data = json_decode( $profile["value"] );

		if ( ! property_exists( $profile_data, 'media_files' ) ) {
			return;
		}

		$started_at = StateFactory::create( 'current_migration' )->load_state( $state_data['migration_state_id'] )->get(
			'started_at',
			false
		);
		$datetime   = new DateTime();

		if ( $started_at ) {
			$datetime->setTimestamp( $started_at );
		}

		$profile_data->media_files->last_migration = $datetime->format( DateTime::ATOM );

		$profile_type   = WPMDB_SAVED_PROFILES_OPTION;
		$saved_profiles = get_site_option( $profile_type );

		if ( isset( $saved_profiles[ $profileID ] ) ) {
			$saved_profiles[ $profileID ]["value"] = json_encode( $profile_data );
			update_site_option( $profile_type, $saved_profiles );
		}
	}

	/**
	 * Handle enqueue_stage filter to enqueue all media files to the queue and return their total bytes.
	 *
	 * @param array|WP_Error $progress
	 * @param StageName      $stage
	 *
	 * @return array|WP_Error
	 * @handles wpmdb_enqueue_stage
	 */
	public function filter_enqueue_stage( $progress, $stage ) {
		if ( Stage::MEDIA_FILES !== $stage ) {
			return $progress;
		}

		if ( is_wp_error( $progress ) ) {
			return $progress;
		}

		$state_data = $this->get_state_data();

		// Not much we can do about this.
		if ( is_wp_error( $state_data ) ) {
			return $state_data;
		}

		$result = $this->initiate_media_file_migration( $state_data );

		// Not much we can do about this.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$progress['initialized_bytes'] = ! empty( $result['queue_status']['size'] ) ? $result['queue_status']['size'] : 0;
		$progress['complete']          = ! isset( $result['recursive_queue'] );

		return $progress;
	}

	/**
	 * Get MF state data.
	 *
	 * @return array|WP_Error
	 */
	private function get_state_data() {
		$current_migration = $this->form_data->getCurrentMigrationData();

		if ( is_wp_error( $current_migration ) ) {
			return $current_migration;
		}

		$mf_options = StateFactory::create( 'media_files' )->load_state( $current_migration['migration_id'] )->get_state();

		if ( is_wp_error( $mf_options ) ) {
			return $mf_options;
		}

		// Need either local or remote uploads base dir depending on intent.
		if ( 'pull' === $current_migration['intent'] ) {
			$folder = StateFactory::create( 'remote_site' )->load_state( $current_migration['migration_id'] )->get( 'wp_upload_dir' );
		} else {
			$folder = StateFactory::create( 'local_site' )->load_state( $current_migration['migration_id'] )->get( 'this_wp_upload_dir' );
		}

		if ( is_wp_error( $folder ) ) {
			return $folder;
		}

		// Need timezone info.
		$date = new DateTime();
		$tz   = $date->getTimezone();

		$state_data = [
			'migration_state_id' => $current_migration['migration_id'],
			'folder'             => $folder,
			'date'               => null,
			'timezone'           => $tz->getName(),
			'stage'              => Stage::MEDIA_FILES,
		];

		if ( ! empty( $mf_options['excludes'] ) ) {
			$state_data['excludes'] = json_encode( $mf_options['excludes'] );
		}

		if ( 'new' === $mf_options['option'] && ! empty( $mf_options['date'] ) ) {
			$state_data['date'] = $mf_options['date'];
		}

		if ( 'new_subsequent' === $mf_options['option'] && ! empty( $mf_options['last_migration'] ) ) {
			$state_data['date'] = $mf_options['last_migration'];
		}

		return $state_data;
	}
}
