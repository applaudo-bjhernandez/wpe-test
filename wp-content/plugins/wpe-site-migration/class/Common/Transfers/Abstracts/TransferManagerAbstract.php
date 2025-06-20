<?php

namespace DeliciousBrains\WPMDB\Common\Transfers\Abstracts;

use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\Queue\Jobs\Core_File_Job;
use DeliciousBrains\WPMDB\Common\Queue\Jobs\File_Job;
use DeliciousBrains\WPMDB\Common\Queue\Jobs\Media_File_Job;
use DeliciousBrains\WPMDB\Common\Queue\Jobs\MUPlugin_File_Job;
use DeliciousBrains\WPMDB\Common\Queue\Jobs\Other_File_Job;
use DeliciousBrains\WPMDB\Common\Queue\Jobs\Plugin_File_Job;
use DeliciousBrains\WPMDB\Common\Queue\Jobs\Root_File_Job;
use DeliciousBrains\WPMDB\Common\Queue\Jobs\Theme_File_Job;
use DeliciousBrains\WPMDB\Common\Queue\Manager;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Transport\TransportManager;
use DeliciousBrains\WPMDB\Data\Stage;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use WP_Error;

/**
 * Class TransferManagerAbstract
 *
 * @package DeliciousBrains\WPMDB\Pro\Transfers\Abstracts
 *
 * @phpstan-import-type StageName from Stage
 */
abstract class TransferManagerAbstract {
	const STABILIZE_TIMEOUT = MINUTE_IN_SECONDS;

	/**
	 * @var Util
	 */
	protected $util;

	/**
	 * @var Manager
	 */
	protected $queueManager;

	public function __construct( Manager $queue_manager, Util $transfer_util ) {
		$this->util         = $transfer_util;
		$this->queueManager = $queue_manager;
	}

	public function register() {
		add_filter( 'wpmdb_process_stage', [ $this, 'filter_process_stage' ], 10, 3 );
	}

	/**
	 * Handle process_stage filter to process files from queue and return their processed bytes and whether stage is complete.
	 *
	 * @param array|WP_Error $progress data from slice of processing, has values for 'processed_bytes' and 'complete'.
	 * @param StageName      $stage
	 * @param string         $migration_id
	 *
	 * @return array|WP_Error
	 * @handles wpmdb_process_stage
	 */
	public function filter_process_stage( $progress, $stage, $migration_id ) {
		if (
			! in_array(
				$stage,
				[
					Stage::MEDIA_FILES,
					Stage::THEME_FILES,
					Stage::PLUGIN_FILES,
					Stage::OTHER_FILES,
					Stage::MUPLUGIN_FILES,
					Stage::CORE_FILES,
					Stage::ROOT_FILES,
				]
			)
		) {
			return $progress;
		}

		if ( is_wp_error( $progress ) ) {
			return $progress;
		}

		// Get previous state data and adjust for current job.
		$state_data = Persistence::getStateData();

		if ( ! isset( $state_data['intent'] ) || ! in_array( $state_data['intent'], [ 'push', 'pull', 'savefile' ] ) ) {
			return $progress;
		}

		// Update the stage in the state data
		$state_data['stage'] = $stage;

		// Empirically set default value
		// Only files that can fit within the calculated payload size
		// will be transferred and removed from the queue.
		$count = apply_filters( 'wpmdb_file_batch_size', 1000 );

		// List jobs
		$jobs = $this->queueManager->list_jobs( $count );

		if ( is_wp_error( $jobs ) ) {
			return $jobs;
		}

		// Filter out jobs that don't belong to the stage
		$jobs = array_filter( $jobs, function ( $job ) use ( $stage ) {
			return is_a( $job, $this->get_stage_file_type( $stage ) );
		} );

		// If the queue is empty or no jobs remaining for this stage, mark stage as complete
		if ( empty( $jobs ) ) {
			$progress['complete'] = true;

			return $progress;
		}

		TransportManager::maybe_wait_before_retry();

		// Process jobs
		$processed  = $this->util->process_file_data( $jobs );
		$remote_url = $state_data['intent'] === 'savefile' ? null : $state_data['url'];
		$results    = $this->manage_file_transfer( $remote_url, $processed, $state_data );

		// Check if the transfer resulted in an error and parse it
		$results = $this->maybe_parse_transfer_error( $results );

		// Update HP mode state
		$hp_state_data = $this->compute_high_performance_mode_state( $results );

		if ( is_wp_error( $results ) ) {
			// If the error qualifies for retry, and we're able to retry, return original progress for go-around.
			if ( TransportManager::get_retry_in_progress() ) {
				return $progress;
			} else {
				// End the migration with error.
				return $results;
			}
		}

		if ( isset( $results['total_transferred'] ) ) {
			$progress['processed_bytes'] += $results['total_transferred'];
		}

		return $progress;
	}

	/**
	 * Computes and sets the state for HP mode based on the file transfer result.
	 *
	 * @param array|WP_Error $results
	 *
	 * @return array
	 */
	private function compute_high_performance_mode_state( $results ) {
		$state_data = Persistence::getStateData();

		//Make sure the retries count is always set in the state array
		if ( ! isset( $state_data['retries'] ) ) {
			$state_data['retries'] = 0;
		}

		// If last transfer failed
		if ( is_wp_error( $results ) ) {
			// Don't stabilize and attempt to step down
			$state_data['stabilizePayloadSize'] = false;
			$state_data['stepDownSize']         = true;
			$state_data['attemptStepDown']      = true;
			$state_data['retries']              = ++$state_data['retries'];
		} else {
			// If we attempted a step-down and it worked, stabilize for a bit.
			if ( isset( $state_data['attemptStepDown'] ) && true === $state_data['attemptStepDown'] ) {
				/**
				 * When the transport mechanism has found a stable payload size, it will attempt to increase the
				 * payload size every 60s. This reduces the risk that a temporary network issue causes the migration
				 * to be stuck at a lower payload size than necessary. Use this filter to adjust the default timeout.
				 *
				 * @param int $timeout Timeout in seconds.
				 */
				$stabilize_timeout = ( int ) apply_filters(
					'wpmdb_hp_mode_stabilize_timeout',
					self::STABILIZE_TIMEOUT
				);

				$state_data['stabilizePayloadSize'] = true;
				$state_data['retries']              = 0;
				$state_data['stepDownSize']         = false;
				$state_data['attemptStepDown']      = false;
				$state_data['stabilizeUntil']       = time() + $stabilize_timeout;
			}

			// If we've stabilized for N seconds, allow step-up again.
			if ( isset( $state_data['stabilizeUntil'] ) && ( time() > $state_data['stabilizeUntil'] ) ) {
				$state_data['stabilizePayloadSize'] = false;
			}

			// Update payload size.
			if ( isset( $results['current_payload_size'] ) ) {
				$state_data['payloadSize'] = $results['current_payload_size'];
			}
		}

		Persistence::saveStateData( $state_data );

		return $state_data;
	}

	/**
	 * Checks if transfer API call result contains any errors
	 * and creates a WP_Error object for the errors found.
	 *
	 * @param array|WP_Error $results
	 *
	 * @return array|WP_Error
	 */
	private function maybe_parse_transfer_error( $results ) {
		if ( is_array( $results ) && isset( $results['error'] ) && $results['error'] === true ) {
			return new WP_Error( $results['message'] );
		}

		return $results;
	}

	/**
	 * Returns the appropriate queue file job class name for a given stage.
	 *
	 * @param StageName $stage
	 *
	 * @return string
	 */
	private function get_stage_file_type( $stage ) {
		switch ( $stage ) {
			case Stage::MEDIA_FILES:
				return Media_File_Job::class;
			case Stage::THEME_FILES:
				return Theme_File_Job::class;
			case Stage::PLUGIN_FILES:
				return Plugin_File_Job::class;
			case Stage::MUPLUGIN_FILES:
				return MUPlugin_File_Job::class;
			case Stage::OTHER_FILES:
				return Other_File_Job::class;
			case Stage::CORE_FILES:
				return Core_File_Job::class;
			case Stage::ROOT_FILES:
				return Root_File_Job::class;
			default:
				return File_Job::class;
		}
	}

	public function post( $payload, $state_data, $action, $remote_url ) {
	}

	public function request( $file, $state_data, $action, $remote_url ) {
	}

	public function handle_push( $processed, $state_data, $remote_url ) {
	}

	public function handle_pull( $processed, $state_data, $remote_url ) {
	}

	public function handle_savefile( $processed, $state_data ) {
	}

	/**
	 *
	 * Logic to handle pushes or pulls of files
	 *
	 * @param string $remote_url
	 * @param array  $processed  list of files to transfer
	 * @param array  $state_data MDB's array of $_POST[] items
	 *
	 * @return mixed|WP_Error
	 * @see $this->ajax_initiate_file_migration
	 *
	 */
	public function manage_file_transfer( $remote_url, $processed, $state_data ) {
		if ( 'pull' === $state_data['intent'] ) {
			return $this->handle_pull( $processed, $state_data, $remote_url );
		}
		if ( 'savefile' === $state_data['intent'] ) {
			return $this->handle_savefile( $processed, $state_data );
		}

		return $this->handle_push( $processed, $state_data, $remote_url );
	}
}
