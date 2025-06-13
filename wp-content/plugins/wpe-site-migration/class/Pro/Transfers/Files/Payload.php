<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers\Files;

use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Migration\MigrationHelper;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\CurrentMigrationState;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Chunker;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Data\Stage;
use DeliciousBrains\WPMDB\Pro\Transfers\Sender;
use Exception;
use WP_Error;

/**
 * Class Payload
 *
 * @package WPMDB\Transfers\Files
 */
class Payload {
	/**
	 * @var Util
	 */
	public $util;

	/**
	 * @var Chunker
	 */
	public $chunker;

	/**
	 * @var Filesystem
	 */
	public $filesystem;

	const PART_SUFFIX = '.part';

	public function __construct(
		Util $util,
		Chunker $chunker,
		Filesystem $filesystem
	) {
		$this->util       = $util;
		$this->chunker    = $chunker;
		$this->filesystem = $filesystem;

		add_filter( 'wpmdb_migration_stats', [ $this, 'filter_migration_stats' ] );
	}

	/**
	 *
	 * Create a payload string based on an array of file data.
	 *
	 * Write string to $stream resource
	 *
	 * @param array    $file
	 * @param array    $meta_data
	 * @param resource $stream
	 * @param string   $file_path
	 *
	 * @return null|WP_Error
	 */
	public function assemble_payload( $file, $meta_data, &$stream, $file_path ) {
		if ( ! file_exists( $file['absolute_path'] ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-payload_file_missing',
				sprintf( __( 'File does not exist - %s', 'wp-migrate-db' ), $file['absolute_path'] )
			);
		}

		$file_name          = $file['name'];
		$file['type']       = 'file';
		$file['md5']        = md5_file( $file['absolute_path'] );
		$file['chunk_size'] = isset( $file['chunk_path'] ) ? filesize( $file['chunk_path'] ) : null;
		$file['encoded']    = true;

		if ( ! isset( $file['size'] ) ) {
			$file['size'] = filesize( $file['absolute_path'] );
		}

		$meta_data['file'] = $file + $meta_data['file'];

		$content = Sender::$start_meta . $file_name . "\n";
		$content .= json_encode( $meta_data ) . "\n";
		$content .= Sender::$end_meta . $file_name . "\n";
		$content .= Sender::$start_payload . $file_name . "\n";

		// Write first chunk of content to the payload
		if ( false === fwrite( $stream, $content ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-payload_file_write_start_error',
				__( 'Unable to write to payload file.', 'wp-migrate-db' )
			);
		}

		$file_stream = fopen( $file_path, 'rb' );

		if ( false === $file_stream ) {
			return new WP_Error(
				'wpmdb-file_transfer-payload_file_open_error',
				sprintf( __( 'Unable to open file %s', 'wp-migrate-db' ), $file_path )
			);
		}

		// Skirts memory limits by copying stream to stream - writes directly to stream
		stream_copy_to_stream( $file_stream, $stream );

		$content = "\n" . Sender::$end_payload . $file_name;
		$content .= "\n" . Sender::$end_sig . "\n";

		if ( false === fwrite( $stream, $content ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-payload_file_write_end_error',
				__( 'Unable to write to payload file.', 'wp-migrate-db' )
			);
		}

		fclose( $file_stream );

		return null;
	}

	/**
	 * Create the payload file ready for transfer.
	 *
	 * @param array $file_list
	 * @param array $state_data
	 * @param int   $bottleneck
	 *
	 * @return resource|array|WP_Error
	 */
	public function create_payload( $file_list, $state_data, $bottleneck ) {
		$handle = Util::tmpfile();

		if ( empty( $handle ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-payload_file_create_error',
				__( 'Unable to create payload file.', 'wp-migrate-db' )
			);
		}

		$count               = 0;
		$sent                = [];
		$payload_contents    = [];
		$chunked             = [];
		$chunking            = false;
		$chunks              = 0;
		$size_modified_count = 0;
		$files_modified      = [];

		foreach ( $file_list as $key => $file ) {
			// Info on fopen() stream
			$fstat = fstat( $handle );
			//get current size to see if it has changed since init
			$current_size = $this->filesystem->filesize( $file['absolute_path'] );
			if ( $current_size !== $file['size'] ) {
				$file['size']     = $current_size;
				$files_modified[] = $file['absolute_path'];
				$size_modified_count++;
			}
			$added_size = $fstat['size'] + $file['size'];

			// If the filesize is less than the bottleneck and adding the file to the payload would push it over the $bottleneck
			// OR the payload already has stuff in it and the next file is a file larger than the bottleneck
			if (
				( $file['size'] < $bottleneck && $added_size >= $bottleneck ) ||
				( 0 !== $fstat['size'] && $file['size'] >= $bottleneck )
			) {
				break;
			}

			$data = [
				'file'  => $file,
				'stage' => $state_data['stage'],
			];

			$file_path = $file['absolute_path'];
			$file_size = filesize( $file_path );

			//Push and file is too large
			if ( $file_size >= $bottleneck && 'push' === $state_data['intent'] ) {
				$chunks   = ceil( $file_size / $bottleneck );
				$chunking = true;
			}

			list( $chunked, $file, $file_path, $chunk_data ) = $this->maybe_get_chunk_data(
				$state_data,
				$bottleneck,
				$chunking,
				$file_path,
				$file,
				$chunks
			);

			$result = $this->assemble_payload( $file, $data, $handle, $file_path );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$sent[] = $file;

			// $payload_contents is used for payload checksum calculation
			// and chunk_number is being incremented on every stage processing iteration
			// which would throw off the md5 value even if it's the same payload, so unsetting it here for more reliable values.
			unset( $file['chunk_number'] );
			$payload_contents[] = $file;
			$count++;
		}

		// Store payload retries in the state.
		$this->record_payload_retries_state( $payload_contents );

		//track number of modified file sizes
		if ( $size_modified_count > 0 ) {
			$this->track_migration_stats( $size_modified_count, $files_modified );
		}

		if ( 'pull' === $state_data['intent'] ) {
			$handle = $this->assemble_payload_metadata( $count, $sent, $handle );

			if ( is_wp_error( $handle ) ) {
				return $handle;
			}
		}

		if ( false === fwrite( $handle, "\n" . Sender::$end_bucket ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-payload_file_write_end_marker_error',
				__( 'Unable to write to payload file.', 'wp-migrate-db' )
			);
		}

		if ( 'push' === $state_data['intent'] ) {
			return array( $count, $sent, $handle, $chunked, $file, $chunk_data );
		}

		return $handle;
	}

	/**
	 * Add stats to tracking.
	 *
	 * @param int   $size_modified_count
	 * @param array $files_modified
	 *
	 * @return bool
	 **/
	private function track_migration_stats( $size_modified_count, $files_modified ) {
		$migration_stats = Persistence::getMigrationStats();

		$existing_count = is_array( $migration_stats ) && array_key_exists( 'files_size_modified', $migration_stats )
			? $migration_stats['file_size_modified']
			: 0;

		$migration_stats['files_size_modified'] = $existing_count + $size_modified_count;

		$existing_files = is_array( $migration_stats ) && array_key_exists( 'files_modified', $migration_stats )
			? $migration_stats['files_modified']
			: [];

		$migration_stats['files_modified'] = array_slice( array_merge( $existing_files, $files_modified ), 0, 10 );

		return Persistence::storeMigrationStats( $migration_stats );
	}

	/**
	 * Maybe create chunked data.
	 *
	 * @param array  $state_data
	 * @param int    $bottleneck
	 * @param bool   $chunking
	 * @param string $file_path
	 * @param array  $file
	 * @param int    $chunks
	 *
	 * @return array
	 */
	public function maybe_get_chunk_data( $state_data, $bottleneck, $chunking, $file_path, $file, $chunks ) {
		if ( ! $chunking ) {
			return array( false, $file, $file_path, [] );
		}
		// Checks if current migration is a 'push' and if the file is too large to transfer
		list( $chunked, $chunk_data ) = $this->chunker->chunk_it(
			$state_data,
			$bottleneck,
			$file_path,
			$file,
			$chunks
		);

		if ( $chunked && false !== $chunked['chunked'] ) {
			$file      = $chunked['file'];
			$file_path = $chunked['file_path'];
		}

		return array( $chunked, $file, $file_path, $chunk_data );
	}

	/**
	 * Read payload line by line and parse out contents.
	 *
	 * @param array    $state_data
	 * @param resource $stream
	 * @param bool     $return Not used
	 *
	 * @return bool|array|WP_Error
	 */
	public function process_payload( $state_data, $stream, $return = false ) {
		$is_meta        = false;
		$is_payload     = false;
		$end_payload    = false;
		$is_skipped     = false;
		$is_bucket_meta = false;
		$bucket_meta    = false;
		$handle         = null;
		$meta           = [];

		while ( ( $line = fgets( $stream ) ) !== false ) {
			if ( false !== strpos( $line, Sender::$start_meta ) ) {
				$is_meta = true;
				continue;
			}

			if ( $is_meta ) {
				$meta    = json_decode( $line, true );
				$is_meta = false;
				continue;
			}

			if ( false !== strpos( $line, Sender::$start_payload ) ) {
				$is_payload = true;

				//For pulls, we're not chunking so use the full filesize, for push check if a chunk size exists, otherwise use the full filesize.
				$filesize = ! empty( $meta['file']['chunk_size'] ) ? $meta['file']['chunk_size'] : $meta['file']['size'];

				$destination_filepath = $this->assemble_filepath_from_payload( $state_data, $meta );

				if ( is_wp_error( $destination_filepath ) ) {
					return $destination_filepath;
				}

				// If handling part of chunked file, use final destination file name for skip test.
				if ( ! empty( $meta['file']['chunked'] ) && $this->has_part_suffix( $destination_filepath ) ) {
					$destination_filepath = $this->trim_part_suffix( $destination_filepath );
				}

				//If the final destination file already exists, skip it.
				if (
					is_numeric( $filesize ) &&
					is_file( $destination_filepath ) &&
					$this->verify_file_from_payload( $destination_filepath, $meta, true )
				) {
					$is_skipped = true;
					fseek( $stream, $filesize, SEEK_CUR );
					continue;
				}

				$dest_and_handle = $this->get_handle_from_metadata( $state_data, $meta );

				if ( is_wp_error( $dest_and_handle ) ) {
					return $dest_and_handle;
				}

				list( $dest, $handle ) = $dest_and_handle;

				if ( ! is_resource( $handle ) ) {
					return new WP_Error(
						'wpmdb-file_transfer-resource_handle_from_metadata_error',
						__( 'Could not create resource handle for payload from metadata.', 'wp-migrate-db' )
					);
				}

				// maybe we can stream the file without buffering
				if ( is_numeric( $filesize ) ) {
					// set up stream copy here
					$streamed_bytes = stream_copy_to_stream( $stream, $handle, $filesize );
					if ( false === $streamed_bytes ) {
						error_log( 'Could not copy stream data to file. ' . print_r( $dest, true ) );

						return new WP_Error(
							'wpmdb-file_transfer-stream_write_error',
							sprintf( __( 'Could not copy stream data to file. File name: %s', 'wp-migrate-db' ), $dest )
						);
					}
					// yay! we did it. Next loop gets the end of payload
					continue;
				}

				//We couldn't determine the filesize so let's bail
				error_log( 'Could not determine payload filesize: ' . print_r( $dest, true ) );

				return new WP_Error(
					'wpmdb-file_transfer-payload_filesize_read_error',
					sprintf( __( 'Could not determine payload filesize. File name: %s', 'wp-migrate-db' ), $dest )
				);
			}

			// If we're skipping a file, jump to the end of the payload
			if ( $is_payload && $is_skipped ) {
				$is_payload  = false;
				$end_payload = true;
				continue;
			}

			if ( $is_payload ) {
				/**
				 * Since we're in a large while loop we need to check if a file's payload
				 * has been read entirely. Files are added to the payload line by line so they
				 * need to read line by line. Sender::$end_payload is the deliminator to say that
				 * a file's contents ends _within_ the payload.
				 */
				if ( false !== strpos( $line, Sender::$end_payload ) ) {
					// Trim trailing newline from end of the created file, thanks fgets()...
					$stat = fstat( $handle );
					ftruncate( $handle, $stat['size'] - 1 );

					fclose( $handle );

					$is_payload  = false;
					$end_payload = true;
					continue;
				}

				$result = $this->create_file_by_line( $line, $handle, $meta['file'] );

				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}

			if ( $end_payload ) {
				if ( ! $is_skipped ) {
					if ( isset( $meta['file']['chunked'] ) && false !== $meta['file']['chunked'] ) {
						if ( isset( $meta['file']['bytes_offset'], $meta['file']['size'] ) && ( (int) $meta['file']['bytes_offset'] === (int) $meta['file']['size'] ) ) {
							//Chunking complete
							$renamed = $this->rename_part_file( $dest, $meta );

							if ( is_wp_error( $renamed ) ) {
								return $renamed;
							}

							if ( ! $renamed ) {
								return new WP_Error(
									'wpmdb-file_transfer-part_file_rename_error',
									sprintf( __( 'Unable to rename part file %s', 'wp-migrate-db' ), $dest )
								);
							}
						}
					} else {
						$result = $this->verify_file_from_payload( $dest, $meta );

						if ( is_wp_error( $result ) ) {
							return $result;
						}
					}
				}

				$is_skipped  = false;
				$end_payload = false;
				continue;
			}

			/**
			 * Bucket meta is information about what's in the payload.
			 *
			 * Presently this includes a count of how many files it contains and
			 * file information from Filesystem::get_file_info() about each file within
			 *
			 */
			if ( false !== strpos( $line, Sender::$start_bucket_meta ) ) {
				$is_bucket_meta = true;
				continue;
			}

			if ( $is_bucket_meta ) {
				$bucket_meta    = json_decode( $line, true );
				$is_bucket_meta = false;
				continue;
			}

			if ( false !== strpos( $line, Sender::$end_bucket ) ) {
				return $bucket_meta;
			}
		}

		return false;
	}

	/**
	 * Verify that a file is from the payload.
	 *
	 * @param string $dest
	 * @param array  $meta
	 * @param bool   $silent
	 *
	 * @return bool|WP_Error
	 */
	public function verify_file_from_payload( $dest, $meta, $silent = false ) {
		// Verify size of file matches what it's supposed to be.
		$file_verified = $this->util->verify_file( $dest, (int) $meta['file']['size'] );

		if ( is_wp_error( $file_verified ) ) {
			return $silent ? false : $file_verified;
		}

		if ( ! $file_verified ) {
			if ( $silent ) {
				return false;
			}

			$msg = sprintf(
				__(
					'File size of source and destination do not match: <br>%1$s<br>Destination size: %2$s, Local size: %3$s',
					'wp-migrate-db'
				),
				$dest,
				filesize( $dest ),
				$meta['file']['size']
			);

			return new WP_Error( 'wpmdb-file_transfer-verify_file_size_error', $msg );
		}

		$md5 = md5_file( $dest );

		if ( $meta['file']['md5'] !== $md5 ) {
			if ( $silent ) {
				return false;
			}

			$msg = sprintf(
				__( 'File MD5\'s do not match for file: %1$s<br>Local MD5: %2$s Remote MD5: %3$s', 'wp-migrate-db' ),
				dirname( $dest ),
				$md5,
				$meta['file']['md5']
			);

			return new WP_Error( 'wpmdb-file_transfer-verify_file_hash_error', $msg );
		}

		return true;
	}

	/**
	 * Give a line of data from fgets() write to a previously created resource(stream).
	 *
	 * @param string   $line
	 * @param resource $handle
	 * @param array    $file_data
	 *
	 * @return bool|WP_Error
	 */
	public function create_file_by_line( $line, $handle, $file_data ) {
		if ( ! $handle || false === fwrite( $handle, $line ) ) {
			error_log( 'Could not write line to file. ' . print_r( $file_data, true ) );

			return new WP_Error(
				'wpmdb-file_transfer-write_line_error',
				sprintf( __( 'Could not write line to file. File name: %s', 'wp-migrate-db' ), $file_data['name'] )
			);
		}

		return false;
	}

	/**
	 * Gets the full path for the payload to be written to.
	 *
	 * @param array $state_data
	 * @param array $meta
	 *
	 * @return string
	 */
	public function assemble_filepath_from_payload( $state_data, $meta ) {
		$stage = $state_data['stage'];

		$file = $this->filesystem->slash_one_direction( $meta['file']['relative_path'] );

		if ( isset( $meta['file']['chunked'] ) && $meta['file']['chunked'] === true ) {
			$file .= self::PART_SUFFIX;
		}

		$dest = Util::get_temp_dir( $stage ) . $file;
		if ( $stage === Stage::MEDIA_FILES ) {
			// Filtered by MST
			$uploads = apply_filters( 'wpmdb_mf_destination_uploads', Util::get_wp_uploads_dir(), $state_data );
			$file    = apply_filters( 'wpmdb_mf_destination_file', $file, $state_data );
			$dest    = $uploads . $file;
		}

		return $dest;
	}

	/**
	 * Get the full file path and an open file handle for the payload being transferred.
	 *
	 * The method will attempt to make the directory the file should be in if it does
	 * not already exist.
	 *
	 * @param array $state_data
	 * @param array $meta
	 *
	 * @return array|WP_Error An array of 'dest' (the full filepath) and 'handle' (the file handle)
	 */
	public function get_handle_from_metadata( $state_data, $meta ) {
		$dest = $this->assemble_filepath_from_payload( $state_data, $meta );

		if ( is_wp_error( $dest ) ) {
			return $dest;
		}

		$dirname = \dirname( $dest );

		if ( ! is_dir( $dirname ) ) {
			$result = $this->create_directory( $dirname );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$result = $this->check_directory_is_writable( $dirname, $dest );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->check_file_is_writable( $dest );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$mode = isset( $meta['file']['chunked'] ) ? 'a+b' : 'w+b';

		// Files need to be deleted before hand when running media stage because they are copied in place
		// 'w' fopen() mode truncates the file before opening to write
		if (
			isset( $meta['file']['chunked'] ) && $meta['file']['chunk_number'] === 1 ||
			! isset( $meta['file']['chunked'] )
		) {
			$mode = 'w+b';
		}

		$handle = fopen( $dest, $mode );

		if ( false === $handle ) {
			return new WP_Error(
				'wpmdb-file_transfer-open_file_handler_error',
				sprintf( __( 'Unable to open file: %s', 'wp-migrate-db' ), $dest )
			);
		}

		return array( $dest, $handle );
	}

	/**
	 * Add metadata to the payload.
	 *
	 * @param int      $count
	 * @param array    $sent
	 * @param resource $handle
	 *
	 * @return resource|WP_Error
	 */
	public function assemble_payload_metadata( $count, $sent, $handle ) {
		// Information about what's in the payload, number of files and an array of file data about the files included
		$bucket_meta = json_encode( compact( 'count', 'sent' ) );

		$bucket_meta_content = Sender::$start_bucket_meta . "\n";
		$bucket_meta_content .= $bucket_meta . "\n";
		$bucket_meta_content .= Sender::$end_bucket_meta;

		if ( false === fwrite( $handle, $bucket_meta_content ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-write_payload_file_error',
				__( 'Unable to write to payload file.', 'wp-migrate-db' )
			);
		}

		return $handle;
	}

	/**
	 * Rename a file part.
	 *
	 * @param string $dest
	 * @param array  $meta
	 *
	 * @return bool|WP_Error
	 */
	public function rename_part_file( $dest, $meta ) {
		// Make sure part file has expected suffix.
		if ( ! $this->has_part_suffix( $dest ) ) {
			return new WP_Error(
				'wpmdb_chunk_file_rename_failed',
				sprintf( __( 'Part file missing expected suffix %s', 'wp-migrate-db' ), $dest )
			);
		}

		// Strip part suffix off end of file.
		$original_filename = $this->trim_part_suffix( $dest );

		if ( $this->filesystem->file_exists( $original_filename ) ) {
			$this->filesystem->unlink( $original_filename );
		}

		$renamed = rename( $dest, $original_filename );

		if ( ! $renamed ) {
			return new WP_Error(
				'wpmdb_chunk_file_rename_failed',
				sprintf( __( 'Unable to rename part file %s', 'wp-migrate-db' ), $dest )
			);
		}

		return $this->verify_file_from_payload( $original_filename, $meta );
	}

	/**
	 * Filters migration stats with payload related data.
	 *
	 * @handles wpmdb_migration_stats
	 *
	 * @param array $migration_stats
	 *
	 * @return array
	 */
	public function filter_migration_stats( $migration_stats ) {
		if ( ! is_array( $migration_stats ) ) {
			return $migration_stats;
		}

		$current_migration = StateFactory::create( 'current_migration' )->load_state( MigrationHelper::get_current_migration_id() );

		try {
			$migration_stats['last_payload_retry_count'] = $current_migration->get( 'last_payload_retry_count' );
		} catch ( Exception $exception ) {
			$migration_stats['last_payload_retry_count'] = 0;
		}

		return $migration_stats;
	}

	/**
	 * Does the destination file have a part suffix?
	 *
	 * @param string $dest
	 *
	 * @return bool
	 */
	private function has_part_suffix( $dest ) {
		return false !== strrpos( $dest, self::PART_SUFFIX, strlen( $dest ) - strlen( self::PART_SUFFIX ) );
	}

	/**
	 * Stores the payload retry attempts the state data.
	 *
	 * @param array $payload_contents
	 *
	 * @return void
	 */
	private function record_payload_retries_state( $payload_contents ) {
		$current_migration = StateFactory::create( 'current_migration' )->load_state( MigrationHelper::get_current_migration_id() );

		if ( is_a( $current_migration, CurrentMigrationState::class ) && ! empty( $payload_contents ) ) {
			try {
				$last_payload_retry_count = (int) $current_migration->get( 'last_payload_retry_count' );
				$last_payload_checksum    = $current_migration->get( 'last_payload_checksum' );

				$payload_checksum = md5( serialize( $payload_contents ) );
				if ( $last_payload_checksum === $payload_checksum ) {
					//increment the counter
					$current_migration->set( 'last_payload_retry_count', ++$last_payload_retry_count, false );
				} else {
					//Reset the counter if the checksums don't match
					$current_migration->set( 'last_payload_retry_count', 0, false );
				}

				$current_migration->set( 'last_payload_checksum', $payload_checksum, false );
				$current_migration->update_state();
			} catch ( Exception $exception ) {
				//Initialize the properties with default values
				$current_migration->set( 'last_payload_retry_count', 0, false );
				$current_migration->set( 'last_payload_checksum', '', false );
				$current_migration->update_state();
			}
		}
	}

	/**
	 * Strip the part suffix from the destination file.
	 *
	 * @param string $dest
	 *
	 * @return string
	 */
	private function trim_part_suffix( $dest ) {
		if ( ! $this->has_part_suffix( $dest ) ) {
			return $dest;
		}

		return substr( $dest, 0, -strlen( self::PART_SUFFIX ) );
	}

	/**
	 * Raises an exception if a directory exists and is not writable.
	 *
	 * @param string $dirname The directory path to check for.
	 * @param string $dest    A file path to be included in the exception's message.
	 *
	 * @return null|WP_Error
	 */
	public function check_directory_is_writable( $dirname, $dest ) {
		if ( is_dir( $dirname ) && ! is_writable( $dirname ) ) {
			if ( $this->filesystem->can_get_file_permissions() ) {
				$message = sprintf(
					__(
						'The parent folder of the file `%s` is not writable. Folder permissions are %s. Please ensure the web server can read from and write to this file\'s parent folder/directory.',
						'wp-migrate-db'
					),
					$dest,
					$this->filesystem->fileperms_as_string( $dirname )
				);
			} else {
				$message = sprintf(
					__(
						'The parent folder of the file `%s` is not writable. Please ensure the web server can read from and write to this file\'s parent folder/directory.',
						'wp-migrate-db'
					),
					$dest
				);
			}

			return new WP_Error( 'wpmdb-file_transfer-check_directory_error', $message );
		}

		return null;
	}

	/**
	 * Creates a directory. Raises an exception on failure.
	 *
	 * @param string $dirname The path of the directory to create.
	 *
	 * @return null|WP_Error
	 */
	public function create_directory( $dirname ) {
		if ( ! $this->filesystem->mkdir( $dirname ) ) {
			$msg = sprintf( __( 'Could not create directory: %s', 'wp-migrate-db' ), $dirname );

			return new WP_Error( 'wpmdb-file_transfer-create_directory_error', $msg );
		}

		return null;
	}

	/**
	 * Raises an exception if the specified file exists but is not writable.
	 *
	 * @param string $filepath The path of the file to check.
	 *
	 * @return null|WP_Error
	 */
	public function check_file_is_writable( $filepath ) {
		if ( file_exists( $filepath ) && ! is_writable( $filepath ) ) {
			// If we are on Unix, get file permissions and include them in the message.
			if ( $this->filesystem->can_get_file_permissions() ) {
				$message = sprintf(
					__(
						'The `%s` file is not writable. It has permissions %s. Please ensure the web server can read from and write to this file.',
						'wp-migrate-db'
					),
					$filepath,
					$this->filesystem->fileperms_as_string( $filepath )
				);
			} else {
				$message = sprintf(
					__(
						'The `%s` file is not writable, please ensure the web server can read from and write to this file.',
						'wp-migrate-db'
					),
					$filepath
				);
			}

			return new WP_Error( 'wpmdb-file_transfer-file_not_writable_error', $message );
		}

		return null;
	}
}
