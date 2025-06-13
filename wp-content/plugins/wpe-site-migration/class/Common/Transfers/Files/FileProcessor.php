<?php

namespace DeliciousBrains\WPMDB\Common\Transfers\Files;

use DateTime;
use DateTimeZone;
use DeliciousBrains\WPMDB\Common\Debug;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Migration\MigrationHelper;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DirectoryIterator;
use Exception;
use DeliciousBrains\WPMDB\Common\Util\Util as CommonUtil;
use WP_Error;

/**
 * Class FileProcessor
 *
 * @package WPMDB\Transfers\Files
 */
class FileProcessor {
	/**
	 * Scanning bottleneck.
	 */
	const BOTTLENECK = 5000;

	/**
	 * Minimum number of items to process before checking whether to continue.
	 */
	const CONTINUE_CHECK_LIMIT_MIN = 100;

	/**
	 * Maximum number of items to process before checking whether to continue.
	 */
	const CONTINUE_CHECK_LIMIT_MAX = 10000;

	/**
	 * Default number of items to process before checking whether to continue.
	 */
	const CONTINUE_CHECK_LIMIT = 1000;

	/**
	 * @var Filesystem
	 */
	public $filesystem;

	/**
	 * FileProcessor constructor.
	 *
	 * @param Filesystem $filesystem
	 */
	public function __construct(
		Filesystem $filesystem
	) {
		$this->filesystem = $filesystem;

		$this->register();
	}

	/**
	 * Registers required action hooks.
	 */
	public function register() {
		add_action( 'wpmdb_migration_complete', [ $this, 'finalize_migration' ] );
		add_action( 'wpmdb_cancellation', [ $this, 'finalize_migration' ] );
	}

	/**
	 * Given an array of directory paths, loops over each dir and returns an array of files and metadata.
	 *
	 * @param array       $directories
	 * @param array       $excludes
	 * @param string      $stage
	 * @param string|null $date
	 * @param string|null $timezone
	 * @param string|null $intent
	 *
	 * @return array|WP_Error
	 */
	public function get_local_files(
		$directories,
		$excludes = array(),
		$stage = '',
		$date = null,
		$timezone = 'UTC',
		$intent = null
	) {
		// Add default excludes.
		$default_excludes = [
			'.DS_Store',
			Util::TMP_FOLDER_PREFIX . '*',
		];

		$excludes   = array_merge( $excludes, $default_excludes );
		$stage_path = ! empty( $stage ) ? CommonUtil::get_stage_base_dir( $stage ) : '';

		/**
		 * How many items can be processed before checking whether we should continue?
		 *
		 * This is not a hard limit, but a yield point to check whether time
		 * or memory limits have been reached for the current process, or whether
		 * processing should be stopped for other reasons, e.g. user requested
		 * the migration be paused or cancelled.
		 *
		 * @param int $continue_check_limit Default 1_000, min 100, max 10_000.
		 */
		$continue_check_limit = min(
			max(
				apply_filters( 'wpmdb_file_processor_continue_check_limit', self::CONTINUE_CHECK_LIMIT, $stage ),
				self::CONTINUE_CHECK_LIMIT_MIN
			),
			self::CONTINUE_CHECK_LIMIT_MAX
		);

		$files      = [];
		$count      = 0;
		$total_size = 0;
		$continue   = true;
		$manifest   = [];

		// Load previous save state from scan manifest file.
		if ( $this->scan_manifest_exists( $intent ) ) {
			$manifest = $this->get_scandir_manifest( $intent );

			if ( is_wp_error( $manifest ) ) {
				return $manifest;
			}
		}

		// Remove anything from directories array that should be excluded.
		$directories = $this->unset_manifest_file(
			$directories,
			$intent
		);

		// Remove directories from list that are already processed.
		if ( ! empty( $manifest ) && ! empty( $directories ) ) {
			$directories = array_filter( $directories, function ( $directory ) use ( $manifest ) {
				return empty( $manifest[ $directory ]['complete'] );
			} );
		}

		// Nothing to do?
		$scan_completed = empty( $directories );

		if ( $scan_completed ) {
			return [
				'meta'  => [
					'count'          => $count,
					'size'           => $total_size,
					'scan_completed' => true,
				],
				'files' => $files,
			];
		}

		foreach ( $directories as $directory ) {
			if ( ! $continue ) {
				break;
			}

			if ( empty( $directory ) || ! is_string( $directory ) ) {
				Debug::log( __FUNCTION__ . ': Invalid directory path:-' );
				Debug::log( $directory );

				return new WP_Error(
					'file-processor-invalid-dir',
					__( 'Could not scan invalid directory path, please check debug log for details.', 'wp-migrate-db' ),
					$directory
				);
			}

			// We have a directory, possibly with items to iterate over.
			$offset = 0;

			// If directory turns out to be a file, check it and skip to next directory.
			if ( ! $this->filesystem->is_dir( $directory ) ) {
				if ( ! $this->should_include_file( $directory, $excludes, $date, $timezone, $stage_path ) ) {
					// Mark as checked.
					list( $manifest, $offset, $count, $continue ) = $this->mark_path_checked(
						$manifest,
						$directory,
						$directory,
						$offset,
						$count,
						$continue_check_limit
					);

					// Save top level directory's state change.
					$saved = $this->save_manifest( $manifest, $intent );

					if ( is_wp_error( $saved ) ) {
						return $saved;
					}

					continue;
				}

				$file_info = $this->get_file_info(
					$directory,
					$stage
				);

				if ( is_array( $file_info ) && ! empty( $file_info['absolute_path'] ) ) {
					// Add to results.
					$total_size            += ! empty( $file_info['size'] ) && is_int( $file_info['size'] ) ? $file_info['size'] : 0;
					$files[ $directory ][] = $file_info;
				}

				// Mark as checked.
				list( $manifest, $offset, $count, $continue ) = $this->mark_path_checked(
					$manifest,
					$directory,
					$directory,
					$offset,
					$count,
					$continue_check_limit
				);

				// Save top level directory's state change.
				$saved = $this->save_manifest( $manifest, $intent );

				if ( is_wp_error( $saved ) ) {
					return $saved;
				}

				continue;
			}

			// Is this top-level directory allowed to be scanned?
			if ( ! $this->should_include_dir( $directory, $excludes, $stage_path ) ) {
				// Mark as checked.
				list( $manifest, $offset, $count, $continue ) = $this->mark_path_checked(
					$manifest,
					$directory,
					$directory,
					$offset,
					$count,
					$continue_check_limit
				);

				// Save top level directory's state change.
				$saved = $this->save_manifest( $manifest, $intent );

				if ( is_wp_error( $saved ) ) {
					return $saved;
				}

				continue;
			}

			// Does the directory have subdirectories we need to scan?
			if ( ! isset( $manifest[ $directory ]['subdirs'] ) ) {
				$scandir_subdirs = $this->recursive_get_dirs( $directory );

				if ( ! empty( $scandir_subdirs ) ) {
					$scandir_subdirs = array_flip( $scandir_subdirs );

					// Grab the subdirs into an array and splurge into the manifest.
					// We already have the full path as the key.
					$manifest[ $directory ]['subdirs'] = array_map(
						function ( $path ) {
							return [ 'offset' => -1, 'complete' => false ];
						},
						$scandir_subdirs
					);
				} else {
					// No subdirs, ensure we don't rescan for subdirs.
					$manifest[ $directory ]['subdirs'] = [];
				}
				unset( $scandir_subdirs );

				// Save top level directory's state change.
				$saved = $this->save_manifest( $manifest, $intent );

				if ( is_wp_error( $saved ) ) {
					return $saved;
				}

				// As getting subdirs could take a while, we may need to skip
				// getting the files for this directory until next go-around.
				$continue = MigrationHelper::should_continue();
			}

			// Get a list of subdirs that have not yet been completed.
			$subdirs = [];
			if ( ! empty( $manifest[ $directory ]['subdirs'] ) ) {
				$subdirs = array_filter( $manifest[ $directory ]['subdirs'], function ( $subdir ) {
					return empty( $subdir['complete'] );
				} );
			}

			if ( ! empty( $subdirs ) ) {
				foreach ( $subdirs as $subdir => $status ) {
					if ( ! $continue ) {
						break;
					}

					list( $new_files, $manifest[ $directory ]['subdirs'], $offset, $count, $total_size, $continue ) = $this->check_files_in_dir(
						$manifest[ $directory ]['subdirs'],
						$subdir,
						$status['offset'],
						$count,
						$total_size,
						$continue_check_limit,
						$excludes,
						$stage,
						$date,
						$timezone,
						$stage_path
					);

					if ( is_wp_error( $new_files ) ) {
						return $new_files;
					} elseif ( ! empty( $new_files ) && is_array( $new_files ) ) {
						if ( ! empty( $files[ $directory ] ) ) {
							$files[ $directory ] = array_merge( $files[ $directory ], $new_files );
						} else {
							$files[ $directory ] = $new_files;
						}
					}
					unset( $new_files );
				}
			}

			// Scan the top-level directory itself too?
			if ( $continue ) {
				// If resuming and this directory partially processed, skip to updated offset.
				$initial_offset = -1;
				if ( ! empty( $manifest[ $directory ]['offset'] ) && is_int( $manifest[ $directory ]['offset'] ) ) {
					$initial_offset = $manifest[ $directory ]['offset'];
				}

				list( $new_files, $manifest, $offset, $count, $total_size, $continue ) = $this->check_files_in_dir(
					$manifest,
					$directory,
					$initial_offset,
					$count,
					$total_size,
					$continue_check_limit,
					$excludes,
					$stage,
					$date,
					$timezone,
					$stage_path
				);

				if ( is_wp_error( $new_files ) ) {
					return $new_files;
				} elseif ( ! empty( $new_files ) && is_array( $new_files ) ) {
					if ( ! empty( $files[ $directory ] ) ) {
						$files[ $directory ] = array_merge( $files[ $directory ], $new_files );
					} else {
						$files[ $directory ] = $new_files;
					}
				}
				unset( $new_files );
			}
		}

		// Save scan manifest.
		$saved = $this->save_manifest( $manifest, $intent );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		// Are we done now?
		if ( ! empty( $manifest ) && ! empty( $directories ) ) {
			$directories = array_filter( $directories, function ( $directory ) use ( $manifest ) {
				return empty( $manifest[ $directory ]['complete'] );
			} );
		}

		$scan_completed = empty( $directories );

		return [
			'meta'  => [
				'count'          => $count,
				'size'           => $total_size,
				'scan_completed' => $scan_completed,
			],
			'files' => $files,
		];
	}

	/**
	 * Should the path be included when compared to the excludes?
	 *
	 * @param string $abs_path
	 * @param array  $excludes
	 * @param string $stage_path
	 *
	 * @return bool
	 */
	public function check_file_against_excludes( $abs_path, $excludes, $stage_path = '' ) {
		if ( empty( $excludes ) ) {
			return true;
		}

		return ! Excludes::shouldExcludeFile( $abs_path, $excludes, $stage_path );
	}

	/**
	 * Compare file modified date against a date and timezone
	 *
	 * Debug: $date = $date->format('Y-m-d H:i:sP');
	 *
	 * @param string $abs_path
	 * @param string $date
	 * @param string $clientTimezone
	 *
	 * @return bool|WP_Error
	 * @throws Exception
	 */
	public function check_file_against_date( $abs_path, $date, $clientTimezone ) {
		if ( is_null( $date ) ) {
			return true;
		}

		if ( ! file_exists( $abs_path ) ) {
			return new WP_Error(
				'wpmdb-file-does-not-exist',
				sprintf( __( 'File %s does not exist', 'wp-migrate-db' ), $abs_path )
			);
		}

		$serverdate     = new DateTime();
		$serverTimeZone = $serverdate->getTimezone();

		// Create client date object with associated timezone
		// so we can compare against filemtime() which uses the server timezone.
		$date = new DateTime(
			$date,
			new DateTimeZone( $clientTimezone )
		);

		$date->setTimezone( new DateTimeZone( $serverTimeZone->getName() ) );

		$timestamp = $date->getTimestamp();
		$fileMTime = filemtime( $abs_path );

		if ( $fileMTime <= $timestamp ) {
			return false;
		}

		return true;
	}

	/**
	 * Scan the directory and return usable files as an array,
	 * also updating section of manifest and stats.
	 *
	 * @param array       $manifest
	 * @param string      $directory
	 * @param int         $initial_offset
	 * @param int         $count
	 * @param int         $total_size
	 * @param int         $continue_check_limit
	 * @param array       $excludes
	 * @param string      $stage
	 * @param string|null $date
	 * @param string|null $timezone
	 * @param string      $stage_path
	 *
	 * @return array|WP_Error
	 */
	private function check_files_in_dir(
		$manifest,
		$directory,
		$initial_offset,
		$count,
		$total_size,
		$continue_check_limit,
		$excludes = [],
		$stage = '',
		$date = null,
		$timezone = 'UTC',
		$stage_path = ''
	) {
		$files    = [];
		$offset   = 0;
		$continue = true;

		// Simple directory iterator.
		$dir_iterator = new DirectoryIterator( $directory );

		if ( ! $dir_iterator->valid() ) {
			$error = new WP_Error(
				'file-processor-dir-scan-error',
				sprintf(
					__( 'Could not scan the directory at %s', 'wp-migrate-db' ),
					$directory
				)
			);

			return [ $error, $manifest, $initial_offset, $count, $total_size, false ];
		}

		// If resuming and this directory partially processed, skip to updated offset.
		if ( 0 <= $initial_offset ) {
			$dir_iterator->seek( $initial_offset );

			if ( ! $dir_iterator->valid() ) {
				$error = new WP_Error(
					'file-processor-resume-dir-scan-error',
					sprintf(
						__( 'Could not resume scan of the directory at %s', 'wp-migrate-db' ),
						$directory
					)
				);

				return [ $error, $manifest, $initial_offset, $count, $total_size, false ];
			}

			$dir_iterator->next();
			$offset = ++$initial_offset;
		}

		// A while loop is needed here, foreach will not work when resuming.
		while ( $dir_iterator->valid() ) {
			if ( ! $continue ) {
				break;
			}

			$file = $dir_iterator->current();

			// Don't add directories to results, but do mark as checked.
			// As we're using the default leaves only option for the iterator,
			// could possibly remove this test, but, you know, just in case? 🤷
			if ( $file->isDir() || $file->isDot() ) {
				// Mark as checked.
				list( $manifest, $offset, $count, $continue ) = $this->mark_path_checked(
					$manifest,
					$file->getPathname(),
					$directory,
					$offset,
					$count,
					$continue_check_limit
				);

				$dir_iterator->next();
				continue;
			}

			// If it is a file that passes all the tests, we want it.
			if ( $this->should_include_file( $file->getPathname(), $excludes, $date, $timezone, $stage_path ) ) {
				$file_info = $this->get_file_info(
					$file->getPathname(),
					$stage
				);

				if ( is_array( $file_info ) && ! empty( $file_info['absolute_path'] ) ) {
					// Add to results.
					$total_size += ! empty( $file_info['size'] ) && is_int( $file_info['size'] ) ? $file_info['size'] : 0;
					$files[]    = $file_info;
				}
			}

			// Mark as checked.
			list( $manifest, $offset, $count, $continue ) = $this->mark_path_checked(
				$manifest,
				$file->getPathname(),
				$directory,
				$offset,
				$count,
				$continue_check_limit
			);

			$dir_iterator->next();
		}

		// If scanning of directory completed, mark as checked.
		if ( $continue ) {
			list( $manifest, $offset, $count, $continue ) = $this->mark_path_checked(
				$manifest,
				$directory,
				$directory,
				$offset,
				$count,
				$continue_check_limit
			);
		}

		return [ $files, $manifest, $offset, $count, $total_size, $continue ];
	}

	/**
	 * Should the file path be included in the migration?
	 *
	 * @param string $abs_path
	 * @param array  $excludes
	 * @param string $date
	 * @param string $timezone
	 * @param string $stage_path
	 *
	 * @return bool
	 */
	private function should_include_file(
		$abs_path,
		$excludes = array(),
		$date = null,
		$timezone = 'UTC',
		$stage_path = ''
	) {
		if ( $this->filesystem->is_dir( $abs_path ) || ! $this->filesystem->is_readable( $abs_path ) ) {
			return false;
		}

		if ( ! $this->check_file_against_excludes( $abs_path, $excludes, $stage_path ) ) {
			return false;
		}

		try {
			$date_ok = $this->check_file_against_date( $abs_path, $date, $timezone );
		} catch ( Exception $e ) {
			// If there is a problem checking date, fallback to including file.
			return true;
		}

		if ( ! is_bool( $date_ok ) || false === $date_ok ) {
			return false;
		}

		return true;
	}

	/**
	 * Should the directory path be included in the migration for scanning?
	 *
	 * @param string $abs_path
	 * @param array  $excludes
	 * @param string $stage_path
	 *
	 * @return bool
	 */
	private function should_include_dir( $abs_path, $excludes = array(), $stage_path = '' ) {
		if ( ! $this->filesystem->is_dir( $abs_path ) || ! $this->filesystem->is_readable( $abs_path ) ) {
			return false;
		}

		if ( ! $this->check_file_against_excludes( $abs_path, $excludes, $stage_path ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the file info needed for a file to be migrated.
	 *
	 * @param string $abs_path
	 * @param string $stage
	 *
	 * @return array|false
	 */
	private function get_file_info( $abs_path, $stage = '' ) {
		$file_info = $this->filesystem->get_file_info(
			wp_basename( $abs_path ),
			dirname( $abs_path ),
			is_link( $abs_path ),
			$stage
		);

		if ( empty( $file_info ) || ! is_array( $file_info ) ) {
			return false;
		}

		return $file_info;
	}

	/**
	 * Should we continue scanning?
	 *
	 * @param int $count
	 * @param int $continue_check_limit
	 *
	 * @return bool
	 */
	private static function should_continue( $count, $continue_check_limit ) {
		$count                = ! is_int( $count ) || 1 > $count ? 1 : $count;
		$continue_check_limit = ! is_int( $continue_check_limit ) ? self::CONTINUE_CHECK_LIMIT : $continue_check_limit;
		$continue_check_limit = min(
			max( $continue_check_limit, self::CONTINUE_CHECK_LIMIT_MIN ),
			self::CONTINUE_CHECK_LIMIT_MAX
		);

		// Restrict count of items handled per slice of scanning.
		// This keeps things responsive, allowing UI updates more regularly
		// than the default background migration process time limit.
		if ( self::BOTTLENECK <= $count ) {
			return false;
		}

		if ( $count % $continue_check_limit === 0 ) {
			return MigrationHelper::should_continue();
		}

		return true;
	}

	/**
	 * Mark an item as having been checked for inclusion, and determine whether more should be checked.
	 *
	 * @param array  $manifest             The scan manifest contents to be updated.
	 * @param string $abs_path             File path just checked.
	 * @param string $directory            Directory path currently being processed.
	 * @param int    $offset               Path's offset into directory's iterator.
	 * @param int    $count                How many items have been handled in current process.
	 * @param int    $continue_check_limit How many items can be processed before checking whether we should continue.
	 *
	 * @return array [$manifest, $offset, $count, $continue]
	 */
	private function mark_path_checked( $manifest, $abs_path, $directory, $offset, $count, $continue_check_limit ) {
		// Update directory's scan manifest resume position and completion status.
		$complete = trailingslashit( $abs_path ) === trailingslashit( $directory );

		$manifest[ $directory ]['offset']   = $offset;
		$manifest[ $directory ]['complete'] = $complete;

		$offset++;
		$count++;
		$continue = static::should_continue( $count, $continue_check_limit );

		return [ $manifest, $offset, $count, $continue ];
	}

	/**
	 * Returns all the directories under the given directory.
	 *
	 * @param string $dir Absolute path to a directory.
	 *
	 * @return array|false
	 *
	 * Believe it or not, this method is way faster than using
	 * either RecursiveDirectoryIterator or glob. 🤷
	 */
	private function recursive_get_dirs( $dir ) {
		Debug::log( __FUNCTION__ . ': Started getting dirs in ' . $dir . ' >>>' );
		$handle = opendir( $dir );

		if ( ! $handle ) {
			Debug::log( __FUNCTION__ . ': Failed to open ' . $dir . ' <<<' );

			return false;
		}

		$dirs = [];
		while ( true ) {
			$item = readdir( $handle );

			// Reached end or hit error.
			if ( false === $item ) {
				break;
			}

			if ( ! in_array( $item, [ '.', '..' ] ) && is_dir( $dir . DIRECTORY_SEPARATOR . $item ) ) {
				$dirs[] = $dir . DIRECTORY_SEPARATOR . $item;
			}
		}

		closedir( $handle );

		// Do any of the found directories have subdirectories?
		if ( ! empty( $dirs ) ) {
			foreach ( $dirs as $subdir ) {
				$subdirs = $this->recursive_get_dirs( $subdir );

				if ( ! empty( $subdirs ) ) {
					$dirs = array_merge( $dirs, $subdirs );
				}
			}
		}

		Debug::log( __FUNCTION__ . ': Finished getting dirs in ' . $dir . ' <<<' );

		return empty( $dirs ) ? false : $dirs;
	}

	/**
	 * Unsets the manifest file entry from a dir list array.
	 *
	 * @param array  $directories
	 * @param string $intent
	 *
	 * @return array
	 */
	public function unset_manifest_file( $directories, $intent = null ) {
		$directories = array_diff(
			$directories,
			array( $this->get_scandir_manifest_filename( $intent ), $this->get_queue_manifest_filename( $intent ) )
		);

		return array_values( $directories );
	}

	/**
	 * Runs finalization actions.
	 */
	public function finalize_migration( $intent = null ) {
		$this->remove_scandir_manifest( $intent );
	}

	/**
	 * Unlinks the manifest file.
	 *
	 * @param string $intent
	 */
	private function remove_scandir_manifest( $intent = null ) {
		$filename = $this->get_scandir_manifest_filename( $intent );

		if ( $this->filesystem->is_file( $filename ) ) {
			$this->filesystem->unlink( $filename );
		}
	}

	/**
	 * Returns the string name of the manifest file based on the current migration id.
	 *
	 * @param string $intent
	 *
	 * @return string|null
	 */
	public function get_scandir_manifest_filename( $intent = null ) {
		$state_data = $intent === 'pull' ? Persistence::getRemoteStateData() : Persistence::getStateData();

		if ( empty( $state_data['migration_state_id'] ) ) {
			return null;
		}

		return Util::get_wp_uploads_dir() . DIRECTORY_SEPARATOR . '.' . $state_data['migration_state_id'] . '-wpmdb-scandir-manifest';
	}

	/**
	 * Returns the string name of the queue's manifest file based on the current migration id.
	 *
	 * @param string $intent
	 *
	 * @return string|null
	 */
	public function get_queue_manifest_filename( $intent = null ) {
		$state_data = $intent === 'pull' ? Persistence::getRemoteStateData() : Persistence::getStateData();

		if ( empty( $state_data['migration_state_id'] ) ) {
			return null;
		}

		return Util::get_wp_uploads_dir() . DIRECTORY_SEPARATOR . Util::get_queue_manifest_file_name( $state_data['migration_state_id'] );
	}

	/**
	 * Saves the current manifest.
	 *
	 * @param array  $manifest
	 * @param string $intent
	 *
	 * @return true|WP_Error
	 */
	public function save_manifest( $manifest = [], $intent = null ) {
		// If nothing to save, don't create empty file.
		if ( empty( $manifest ) ) {
			return true;
		}

		if ( ! is_array( $manifest ) ) {
			return new WP_Error(
				'save-scan-manifest-not-array',
				__( 'Scan manifest file data not an array.' )
			);
		}

		$manifest_filename = $this->get_scandir_manifest_filename( $intent );

		if ( empty( $manifest_filename ) ) {
			return new WP_Error(
				'save-scan-manifest-empty-filename',
				__( 'Scan manifest file name could not be retrieved.' )
			);
		}

		$result = $this->filesystem->put_contents( $manifest_filename, json_encode( $manifest ) );

		if ( ! $result ) {
			return new WP_Error(
				'save-scan-manifest',
				sprintf( __( 'Scan manifest file could not be saved at "%s"' ), $manifest_filename )
			);
		}

		return $result;
	}

	/**
	 * Checks whether a manifest file exists for the current migration.
	 *
	 * @param string $intent
	 *
	 * @return bool
	 */
	public function scan_manifest_exists( $intent = null ) {
		return $this->filesystem->is_file( $this->get_scandir_manifest_filename( $intent ) );
	}

	/**
	 * Retrieves the saved manifest data.
	 *
	 * @param string $intent
	 *
	 * @return mixed|false|WP_Error
	 */
	public function get_scandir_manifest( $intent = null ) {
		$manifest_filename = $this->get_scandir_manifest_filename( $intent );

		if ( empty( $manifest_filename ) ) {
			return new WP_Error(
				'get-scan-manifest-empty-filename',
				__( 'Scan manifest file name could not be retrieved.' )
			);
		}

		if ( ! $this->scan_manifest_exists( $intent ) ) {
			return new WP_Error(
				'get-scan-manifest-not-found',
				__( 'Scan manifest file does not exist.' )
			);
		}

		$file_data = $this->filesystem->get_contents( $manifest_filename );

		if ( false === $file_data ) {
			return new WP_Error(
				'get-scan-manifest-open',
				sprintf(
					__( 'Scan manifest file could not be opened at "%s".', 'wp-migrate-db' ),
					$this->get_scandir_manifest_filename( $intent )
				)
			);
		}

		if ( empty( $file_data ) ) {
			return new WP_Error(
				'get-scan-manifest-empty',
				sprintf(
					__( 'Scan manifest file is empty at "%s".', 'wp-migrate-db' ),
					$this->get_scandir_manifest_filename( $intent )
				)
			);
		}

		return json_decode( $file_data, true );
	}
}
