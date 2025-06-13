<?php

namespace DeliciousBrains\WPMDB\Common\Transfers\Files;

use DeliciousBrains\WPMDB\Common\Debug;
use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\RemotePost;
use DeliciousBrains\WPMDB\Common\Migration\MigrationHelper;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;
use DeliciousBrains\WPMDB\Common\Queue\Manager;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Util\ZipAndEncode;
use DeliciousBrains\WPMDB\Common\Util\Util as Common_Util;
use DeliciousBrains\WPMDB\Data\Stage;
use DI\DependencyException;
use DI\NotFoundException;
use DirectoryIterator;
use Exception;
use Requests;
use Requests_Response;
use WP_Error;
use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @phpstan-import-type StageName from Stage
 */
class Util {
	public $filesystem;

	/**
	 * @var Http
	 */
	private $http;

	/**
	 * @var ErrorLog
	 */
	private $error_log;

	/**
	 * @var Helper
	 */
	private $http_helper;

	/**
	 * @var RemotePost
	 */
	private $remote_post;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var MigrationStateManager
	 */
	private $migration_state_manager;

	/**
	 * @var Common_Util
	 */
	private $util;

	const TMP_FOLDER_PREFIX = 'wpmdb-tmp';

	public function __construct(
		Filesystem $filesystem,
		Http $http,
		ErrorLog $error_log,
		Helper $http_helper,
		RemotePost $remote_post,
		Settings $settings,
		MigrationStateManager $migration_state_manager,
		Common_Util $util
	) {
		$this->filesystem              = $filesystem;
		$this->http                    = $http;
		$this->error_log               = $error_log;
		$this->http_helper             = $http_helper;
		$this->remote_post             = $remote_post;
		$this->settings                = $settings->get_settings();
		$this->migration_state_manager = $migration_state_manager;
		$this->util                    = $util;

		add_filter( 'wpmdb_theoretical_transfer_bottleneck', function ( $bottleneck ) {
			return $this->get_transfer_bottleneck();
		} );
	}

	/**
	 * Get a list of files to be migrated from the remote.
	 *
	 * @param array  $directories
	 * @param string $action
	 * @param array  $excludes
	 * @param string $date
	 * @param string $timezone
	 *
	 * @return array|WP_Error
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	public function get_remote_files( array $directories, $action, $excludes, $date = null, $timezone = null ) {
		// POST to remote to get list of files
		$state_data = $this->migration_state_manager->set_post_data();

		if ( is_wp_error( $state_data ) ) {
			return $state_data;
		}

		$data                       = array();
		$data['action']             = $action;
		$data['intent']             = $state_data['intent'];
		$data['migration_state_id'] = $state_data['migration_state_id'];
		$data['folders']            = json_encode( $directories );
		$data['excludes']           = json_encode( $excludes );
		$data['stage']              = $state_data['stage'];
		$data['sig']                = $this->http_helper->create_signature( $data, $state_data['key'] );

		if ( ! is_null( $date ) ) {
			$data['date'] = $date;
		}

		if ( ! is_null( $timezone ) ) {
			$data['timezone'] = $timezone;
		}

		$ajax_url = trailingslashit( $state_data['url'] ) . 'wp-admin/admin-ajax.php';
		$response = $this->remote_post->post( $ajax_url, $data, __FUNCTION__ );
		$response = $this->remote_post->verify_remote_post_response( $response );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response['data'] = json_decode( ZipAndEncode::decode( $response['data'] ), true );

		if ( isset( $response['wpmdb_error'] ) ) {
			return $response;
		}

		if ( ! $response['success'] ) {
			return new WP_Error( 'wpmdbtransfers_invalid_file_list', $response['data'] );
		}

		return $response['data'];
	}

	/**
	 * Save queue status to the remote site.
	 *
	 * @param array  $queue_status
	 * @param string $action
	 *
	 * @return Requests_Response|WP_Error
	 * @throws Exception
	 */
	public function save_queue_status_to_remote( array $queue_status, $action ) {
		$state_data = $this->migration_state_manager->set_post_data();

		if ( is_wp_error( $state_data ) ) {
			return $state_data;
		}

		$data                    = array();
		$data['action']          = $action;
		$data['intent']          = $state_data['intent'];
		$data['stage']           = $state_data['stage'];
		$data['remote_state_id'] = MigrationHelper::get_current_migration_id();
		$data['sig']             = $this->http_helper->create_signature( $data, $state_data['key'] );

		$data['queue_status'] = base64_encode( gzencode( json_encode( $queue_status ) ) );

		$ajax_url = trailingslashit( $state_data['url'] ) . 'wp-admin/admin-ajax.php';

		return $this->remote_post_and_verify( $ajax_url, $data );
	}

	/**
	 * Fire POST at remote and check for the 'wpmdb_error' key in response
	 *
	 * @param string $ajax_url
	 * @param array  $data
	 *
	 * @return Requests_Response|WP_Error
	 * @throws Exception
	 */
	public function remote_post_and_verify( $ajax_url, $data, $headers = array() ) {
		$requests_options = $this->get_requests_options();

		try {
			$response = Requests::post( $ajax_url, $headers, $data, $requests_options );
		} catch ( Exception $e ) {
			return new WP_Error( 'wpmdb_error', $e->getMessage() );
		}

		$response_body = json_decode( $response->body, true );

		if ( isset( $response_body['wpmdb_error'] ) ) {
			throw new Exception( $response_body['body'] );
		}

		return $response;
	}

	/**
	 * Log and generate WP_Error with transfer related error code.
	 *
	 * @param string $msg
	 * @param array  $data
	 *
	 * @return WP_Error
	 */
	public function log_and_return_error( $msg, $data = array() ) {
		$this->error_log->log_error( $msg, $data );

		return new WP_Error( 'wpmdb_transfer_error', $msg, $data );
	}

	/**
	 *
	 * Handles individual file transfer errors
	 *
	 * @param string $message
	 *
	 * @return array
	 */
	public function fire_transfer_errors( $message ) {
		error_log( $message );
		$this->error_log->log_error( $message );

		return [
			'error'   => true,
			'message' => $message,
		];
	}

	/**
	 * @return array
	 */
	public function get_requests_options() {
		// Listen to SSL verify setting
		$wpmdb_settings   = $this->settings;
		$sslverify        = 1 === $wpmdb_settings['verify_ssl'];
		$requests_options = [];

		// Make Requests cURL transport wait 45s for timeouts
		$hooks = new \Requests_Hooks();
		$hooks->register(
			'curl.before_send',
			function ( $handle ) {
				$remote_cookie = Persistence::getRemoteWPECookie();
				if ( false !== $remote_cookie ) {
					curl_setopt( $handle, CURLOPT_COOKIE, 'wpe-auth=' . $remote_cookie );
				}
				curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 45 );
				curl_setopt( $handle, CURLOPT_TIMEOUT, 45 );
				curl_setopt( $handle, CURLOPT_ENCODING, 'gzip,deflate' );
			}
		);

		$requests_options['hooks']     = $hooks;
		$requests_options['useragent'] = $this->util->get_requests_user_agent();
		if ( ! $sslverify ) {
			$requests_options['verify'] = false;
		}

		return $requests_options;
	}

	/**
	 * Check's that expected directory for file transfer stage migrated. Always fires at the migration destination.
	 *
	 * @param StageName $stage
	 *
	 * @throws Exception
	 */
	public function check_stage_directory( $stage ) {
		$file_path = Common_Util::get_stage_base_dir( $stage );
		if ( ! file_exists( $file_path ) ) {
			throw new Exception(
				sprintf( __( 'The following directory failed to transfer: <br> %s', 'wp-migrate-db' ), $file_path )
			);
		}
	}

	/**
	 * Saves queue data to the manifest file
	 *
	 * @param array     $data
	 * @param StageName $stage
	 * @param string    $migration_state_id
	 * @param bool      $full_site_export
	 *
	 * @return bool|int
	 * @throws Exception
	 */
	public function save_queue_status( array $data, $stage, $migration_state_id, $full_site_export = false ) {
		$tmp_path = $this->get_queue_tmp_path( $stage, $full_site_export );

		//Remove any existing temporary folders
		$this->cleanup_existing_temp_folders( $stage );

		if ( ! $this->filesystem->mkdir( $tmp_path ) ) {
			throw new Exception( sprintf( __( 'Unable to create folder for file transfers: %s' ), $tmp_path ) );
		}

		//Auto-generate .htaccess file to prevent direct file access on apache
		if ( $stage !== Stage::MEDIA_FILES ) {
			self::prevent_direct_access_with_htaccess( $tmp_path );
		}

		$filename = $this->get_queue_manifest_file_name( $migration_state_id );
		$manifest = @file_put_contents( $tmp_path . DIRECTORY_SEPARATOR . $filename, json_encode( $data ) );

		if ( ! $manifest ) {
			throw new Exception(
				sprintf(
					__(
						'Unable to create the transfer manifest file. Verify the web server can write to this file/folder: `%s`',
						'wp-migrate-db'
					),
					$tmp_path . DIRECTORY_SEPARATOR . '.manifest'
				)
			);
		}

		return $manifest;
	}

	/**
	 * Get stored queue manifest array.
	 *
	 * @param string $stage
	 * @param string $migration_state_id
	 * @param bool   $full_site_export
	 *
	 * @return array|false
	 */
	public function get_queue_status( $stage, $migration_state_id, $full_site_export = false ) {
		$filename  = $this->get_queue_manifest_file_name( $migration_state_id );
		$tmp_path  = $this->get_queue_tmp_path( $stage, $full_site_export );
		$file_path = $tmp_path . DIRECTORY_SEPARATOR . $filename;
		$manifest  = is_file( $file_path ) ? @file_get_contents( $file_path ) : false;

		if ( false !== $manifest ) {
			return json_decode( $manifest, true );
		}

		return false;
	}

	/**
	 * Get queue tmp path.
	 *
	 * @param StageName $stage The name of the stage to get the path for.
	 * @param bool      $full_site_export
	 *
	 * @return string
	 */
	private function get_queue_tmp_path( $stage, $full_site_export = false ) {
		//@todo avoid passing full_site_export down to here.
		if ( $full_site_export === true || $stage === Stage::MEDIA_FILES ) {
			return self::get_wp_uploads_dir();
		}

		return self::get_temp_dir( $stage );
	}

	/**
	 * Get manifest file name.
	 *
	 * @param $migration_state_id
	 *
	 * @return string
	 */
	public static function get_queue_manifest_file_name( $migration_state_id ) {
		return '.' . $migration_state_id . '-manifest';
	}

	public function cleanup_media_migration() {
		$uploads = self::get_wp_uploads_dir();
		$this->remove_manifests( $uploads );

		return true;
	}

	/**
	 * Will look for a tmp folder to remove based on the $stage param (themes, plugins)
	 *
	 * @param StageName $stage The name of the stage to remove the temporary folder for.
	 *
	 * @return bool
	 */
	public function remove_tmp_folder( $stage ) {
		$fs = $this->filesystem;

		if ( $stage === Stage::MEDIA_FILES ) {
			return $this->cleanup_media_migration();
		}

		$tmp_folder = self::get_temp_dir( $stage );
		if ( $fs->file_exists( $tmp_folder ) ) {
			if ( $fs->is_dir( $tmp_folder ) ) {
				return $fs->rmdir( $tmp_folder, true );
			}
		}

		return true;
	}

	/**
	 *
	 * Verify a file is the correct size.
	 *
	 * Will throw an Exception if the given file does not exist.
	 *
	 * @param string $filepath
	 * @param int    $expected_size
	 *
	 * @return bool|WP_Error
	 */
	public function verify_file( $filepath, $expected_size ) {
		if ( ! file_exists( $filepath ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-file_not_found_error',
				sprintf( __( 'File does not exist: %s', 'wp-migrate-db' ), $filepath )
			);
		}

		$filesystem_size = filesize( $filepath );
		if ( $filesystem_size !== (int) $expected_size ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue files to be transferred.
	 *
	 * @param array   $files
	 * @param Manager $queue_manager
	 * @param string  $stage
	 *
	 * @return bool|WP_Error
	 */
	public function enqueue_files( $files, $queue_manager, $stage ) {
		foreach ( $files as $file ) {
			$enqueued = $queue_manager->enqueue_file( $file, $stage );

			if ( is_wp_error( $enqueued ) ) {
				return new WP_Error(
					'enqueue-file-error',
					sprintf(
						__( 'Could not add file "%1$s" to queue.<br>Database Error: %2$s', 'wp-migrate-db' ),
						$file['absolute_path'],
						$enqueued->get_error_message()
					)
				);
			}
		}

		return true;
	}

	/**
	 * Clean up any temporary file chunks.
	 *
	 * @param string $suffix
	 *
	 * @return void
	 */
	public function cleanup_temp_chunks( $suffix = 'tmpchunk' ) {
		$dir      = Chunker::get_chunk_base();
		$iterator = new DirectoryIterator( $dir );

		foreach ( $iterator as $fileInfo ) {
			if ( ! $fileInfo->isDot() ) {
				$name = $fileInfo->getFilename();

				if ( preg_match( "/(([a-z0-9]+-){5})$suffix/", $name ) && $fileInfo->isFile() ) {
					$this->filesystem->unlink( $dir . $name );
				}
			}
		}
	}

	/**
	 * Extracts the theme folder name from a given path.
	 *
	 * @param string $local_path
	 * @param string $temp_path
	 *
	 * @return false|string
	 */
	public function get_theme_folder_name( $local_path, $temp_path ) {
		Debug::log( __FUNCTION__ . ': local_path "' . $local_path . '", temp_path  “' . $temp_path . '”.' );

		$last = basename( str_replace( '\\', '/', $local_path ) );

		// Theme found at top level of temp folder.
		if ( $this->filesystem->file_exists( $temp_path . $last ) ) {
			Debug::log( __FUNCTION__ . ': Returning "' . $last . '".' );

			return $last;
		}

		// Themes are allowed to be nested by one folder, but no more.
		// Get penultimate and tail directories,
		// and then check whether penultimate plus tail are in tmp.
		$pieces     = explode( DIRECTORY_SEPARATOR, $local_path );
		$num_pieces = count( $pieces );

		if ( 1 < $num_pieces ) {
			$tail        = array_pop( $pieces );
			$penultimate = array_pop( $pieces );
			$subdir      = $penultimate . DIRECTORY_SEPARATOR . $tail;

			if ( $this->filesystem->file_exists( $temp_path . $subdir ) ) {
				Debug::log( __FUNCTION__ . ': Returning "' . $subdir . '".' );

				return $subdir;
			}
		}

		Debug::log( __FUNCTION__ . ': Failed to get theme folder.' );

		return false;
	}

	/**
	 * Process data
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function process_file_data( $data ) {
		$result_set = [];

		if ( ! empty( $data ) ) {
			foreach ( $data as $size => $record ) {
				$display_path                  = $record->file['subpath'];
				$record->file['relative_path'] = $display_path;

				$result_set[] = $record->file;
			}
		}

		return $result_set;
	}

	public static function get_wp_uploads_dir() {
		$upload_info = wp_get_upload_dir();

		return $upload_info['basedir'];
	}

	/**
	 * @param $directory
	 */
	public function remove_manifests( $directory ) {
		$iterator = new DirectoryIterator( $directory );

		foreach ( $iterator as $fileInfo ) {
			if ( ! $fileInfo->isDot() ) {
				$name = $fileInfo->getFilename();

				if ( preg_match( "/(([a-z0-9]+-){5})manifest/", $name ) && $fileInfo->isFile() ) {
					$this->filesystem->unlink( $directory . DIRECTORY_SEPARATOR . $name );
				}
			}
		}
	}

	/**
	 *
	 * @return int
	 */
	public function get_transfer_bottleneck() {
		$bottleneck = $this->util->get_max_upload_size();

		// Subtract 250 KB from min for overhead
		$bottleneck -= 250000;

		return $bottleneck;
	}

	/**
	 * Enables the bottleneck-ed recursive file scanner.
	 */
	public static function enable_scandir_bottleneck() {
		add_filter( 'wpmdb_bottleneck_dir_scan', function ( $bottleneck ) {
			return true;
		} );
	}

	/**
	 * @param StageName $base
	 *
	 * @return array
	 */
	public function is_tmp_folder_writable( $base = Stage::THEME_FILES ) {
		$tmp             = self::get_temp_dir( $base );
		$test_file       = $tmp . '/test.php';
		$renamed_file    = $tmp . '/test-2.php';

		$return = [
			'status' => true,
		];

		if ( ! $this->filesystem->mkdir( $tmp ) ) {
			$message = sprintf(
				__( 'File transfer error - Unable to create a temporary folder. (%s)', 'wp-migrate-db' ),
				$tmp
			);
			$this->error_log->log_error( $message );

			return [
				'status'  => false,
				'message' => $message,
			];
		}

		if ( method_exists( 'WpeCommon', 'get_wpe_auth_cookie_value' ) ) {
			return $return;
		}

		if ( ! $this->filesystem->touch( $test_file ) ) {
			$message = sprintf(
				__( 'File transfer error - Unable to create a PHP file on the server. (%s)', 'wp-migrate-db' ),
				$test_file
			);
			$this->error_log->log_error( $message );

			return [
				'status'  => false,
				'message' => $message,
			];
		}

		if ( ! file_put_contents( $test_file, 'test' ) ) {
			$message = sprintf(
				__(
					'File transfer error - Unable to update file contents using using PHP\'s file_put_contents() function. (%s)',
					'wp-migrate-db'
				),
				$test_file
			);
			$this->error_log->log_error( $message );

			return [
				'status'  => false,
				'message' => $message,
			];
		}

		if ( ! rename( $test_file, $renamed_file ) ) {
			$message = sprintf(
				__(
					'File transfer error - Unable to move file to the correct location using PHP\'s rename() function. (%s)',
					'wp-migrate-db'
				),
				$renamed_file
			);
			$this->error_log->log_error( $message );

			return [
				'status'  => false,
				'message' => $message,
			];
		}

		//Clean up
		if ( ! $this->remove_tmp_folder( $base ) ) {
			$message = sprintf(
				__(
					'File transfer error - Unable to delete file using PHP\'s unlink() function. (%s)',
					'wp-migrate-db'
				),
				$renamed_file
			);
			$this->error_log->log_error( $message );

			return [
				'status'  => false,
				'message' => $message,
			];
		}

		return $return;
	}

	/**
	 * Where to store files as they're being transferred
	 *
	 * @param StageName $stage
	 *
	 * @return bool|mixed|void
	 */
	public static function get_temp_dir( $stage ) {
		$suffix = Stage::get_stage_temp_dir_suffix( $stage );

		$tmp_dir = self::TMP_FOLDER_PREFIX;

		$is_source = Common_Util::is_source();
		if ( $is_source ) {
			$tmp_dir .= '-' . $suffix;
		}

		$migration_id = StateFactory::create( 'current_migration' )->load_state( null )->get( 'migration_id' );
		if ( ! empty( $migration_id ) ) {
			$tmp_dir .= sprintf( '-%s', md5( $migration_id ) );
		}

		$temp_base = $is_source ? Filesystem::get_upload_info() : Common_Util::get_stage_base_dir( $stage );
		$temp_dir  = $temp_base . DIRECTORY_SEPARATOR . $tmp_dir . DIRECTORY_SEPARATOR;

		return apply_filters( 'wpmdb_transfers_temp_dir', $temp_dir );
	}

	/**
	 * Removes stage temp folders.
	 *
	 * @param string $stage
	 *
	 * @return void
	 */
	public function cleanup_existing_temp_folders( $stage ) {
		$stage_base_dir = Common_Util::get_stage_base_dir( $stage );

		foreach ( glob( $stage_base_dir . DIRECTORY_SEPARATOR . self::TMP_FOLDER_PREFIX . '*' ) as $temp_folder ) {
			$this->filesystem->rmdir( $temp_folder, true );
		}
	}

	/**
	 * Sanitizes a provided file path.
	 * If the filename includes a path, it will get split and only the last part will be sanitized.
	 *
	 * @param string $file_path
	 *
	 * @return string
	 */
	public static function sanitize_file_path( $file_path ) {
		//split path
		$split = explode( DIRECTORY_SEPARATOR, $file_path );

		//sanitize last part
		$file_path = array_pop( $split );
		$split[]   = sanitize_file_name( $file_path );

		return implode( DIRECTORY_SEPARATOR, $split );
	}

	/**
	 * Exclude plugins from the plugins list.
	 *
	 * @param array $plugins
	 * @param bool  $exclude_mdb When set to true, WP Migrate lite/pro will be excluded
	 *
	 * @return array
	 */
	public static function filter_excluded_plugins( $plugins, $exclude_mdb = true ) {
		$excluded_plugins = true === $exclude_mdb ? [ 'wp-migrate-db' ] : [];
		$excluded_plugins = apply_filters( 'wpmdb_excluded_plugins', $excluded_plugins );

		$filtered_plugins = [];

		foreach ( $plugins as $key => $plugin ) {
			if ( Common_Util::array_search_string_begin_with( $key, $excluded_plugins ) ) {
				continue;
			}
			$filtered_plugins[ $key ] = $plugin;
		}

		return $filtered_plugins;
	}

	/**
	 * Generates a .htaccess file in the given path with a deny all rule.
	 *
	 * @param string $file_path
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function prevent_direct_access_with_htaccess( $file_path ) {
		if ( ! is_dir( $file_path ) ) {
			return false;
		}

		//Create .htaccess file
		$htaccess_file = fopen( $file_path . DIRECTORY_SEPARATOR . '.htaccess', 'w+' );

		if ( empty( $htaccess_file ) ) {
			error_log( sprintf( "WPMDB: Unable to open .htaccess file for writing in %s", $file_path ) );

			return false;
		}

		if ( false === fwrite( $htaccess_file, "Satisfy all\nOrder deny,allow\nDeny from all" ) ) {
			error_log( sprintf( "WPMDB: Unable to write to .htaccess file in %s", $file_path ) );

			return false;
		}

		fclose( $htaccess_file );

		return true;
	}

	/**
	 * Checks if a directory is empty
	 *
	 * @param string $dir
	 * @param array  $excludes
	 * @param string $stage_path
	 *
	 * @return bool
	 */
	public static function is_empty_dir( $dir, $excludes = [], $stage_path = '' ) {
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		//Always exclude .DS_Store
		$excludes[] = '.DS_Store';

		//Always exclude WPMDB tmp directories
		$excludes[]   = self::TMP_FOLDER_PREFIX . '*';
		$path         = realpath( $dir );
		$dir_iterator = new RecursiveDirectoryIterator( $path );

		//Ignore [., ..]
		$dir_iterator->setFlags( FilesystemIterator::SKIP_DOTS );

		$files = new RecursiveIteratorIterator(
		// Filter iterator results
			new RecursiveCallbackFilterIterator( $dir_iterator,
				function ( $file, $key, $iterator ) use ( $excludes, $stage_path ) {
					// If folder, allow recursion to look for files
					if ( $file->isReadable() && $iterator->hasChildren() ) {
						return true;
					}

					// Make sure files are not excluded files
					return $file->isReadable() && $file->isFile() && ! Excludes::shouldExcludeFile( $file->getPathname(),
							$excludes,
							$stage_path );
				} )
		);

		return iterator_count( $files ) === 0;
	}

	/**
	 * Creates a temporary file resource handle.
	 *
	 * @return resource|null
	 */
	public static function tmpfile() {
		if ( function_exists( 'tmpfile' ) ) {
			$tmp_file = tmpfile();
		}

		return apply_filters( 'wpmdb_transfers_stream_handle', $tmp_file );
	}
}
