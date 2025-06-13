<?php

namespace DeliciousBrains\WPMDB\Common\Queue;

use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use Exception;
use WP_Error;

class QueueHelper {
	public $filesystem;

	/**
	 * @var Http
	 */
	private $http;

	/**
	 * @var Helper
	 */
	private $http_helper;

	/**
	 * @var Util
	 */
	private $transfer_util;

	/**
	 * @var Manager
	 */
	private $queue_manager;

	/**
	 * @var \DeliciousBrains\WPMDB\Common\Util\Util
	 */
	private $util;

	public function __construct(
		Filesystem $filesystem,
		Http $http,
		Helper $http_helper,
		Util $transfer_util,
		Manager $queue_manager,
		\DeliciousBrains\WPMDB\Common\Util\Util $util
	) {
		$this->filesystem    = $filesystem;
		$this->http          = $http;
		$this->http_helper   = $http_helper;
		$this->transfer_util = $transfer_util;
		$this->queue_manager = $queue_manager;
		$this->util          = $util;
	}

	/**
	 * Populate the queue with given file data.
	 *
	 * @param array  $file_data
	 * @param string $intent
	 * @param string $stage
	 * @param string $migration_state_id
	 * @param bool   $full_site_export
	 *
	 * @return array|WP_Error
	 * @throws Exception
	 */
	public function populate_queue( $file_data, $intent, $stage, $migration_state_id, $full_site_export = false ) {
		if ( ! $file_data ) {
			return $this->transfer_util->log_and_return_error( __( 'File list empty or incomplete. Please contact support.' ) );
		}

		if ( is_wp_error( $file_data ) ) {
			return $file_data;
		}

		foreach ( $file_data['files'] as $item ) {
			if ( is_array( $item ) ) {
				$enqueued = $this->transfer_util->enqueue_files( $item, $this->queue_manager, $stage );

				if ( is_wp_error( $enqueued ) ) {
					return $enqueued;
				}
			}
		}

		$queue_status = [
			'total' => $file_data['meta']['count'],
			'size'  => $file_data['meta']['size'],
		];

		//Always store local manifest even for push intents, to keep track of recursive scanning items count.
		$complete_status = $this->store_local_manifest(
			$queue_status,
			$file_data,
			$stage,
			$migration_state_id,
			$full_site_export
		);

		if ( is_wp_error( $complete_status ) ) {
			return $complete_status;
		}

		return $complete_status;
	}

	/**
	 * Saves the local manifest.
	 *
	 * @param array  $queue_status
	 * @param array  $file_data
	 * @param string $stage
	 * @param string $migration_state_id
	 *
	 * @return bool|mixed
	 */
	private function store_local_manifest(
		$queue_status,
		$file_data,
		$stage,
		$migration_state_id,
		$full_site_export = false
	) {
		$queue_status = $this->concat_existing_queue_items(
			$queue_status,
			$file_data,
			$stage,
			$migration_state_id,
			$full_site_export
		);

		try {
			$this->transfer_util->save_queue_status( $queue_status, $stage, $migration_state_id, $full_site_export );
		} catch ( Exception $e ) {
			return $this->transfer_util->log_and_return_error( $e->getMessage() );
		}

		return $queue_status;
	}

	/**
	 * Concat existing queue status if exists.
	 *
	 * @param array  $queue_status
	 * @param array  $file_data
	 * @param string $stage
	 * @param string $migration_state_id
	 *
	 * @return array
	 */
	private function concat_existing_queue_items(
		$queue_status,
		$file_data,
		$stage,
		$migration_state_id,
		$full_site_export = false
	) {
		//attempt to load queue status
		$stored_queue = $this->transfer_util->get_queue_status( $stage, $migration_state_id, $full_site_export );
		if ( false !== $stored_queue ) {
			$queue_status          = $stored_queue;
			$queue_status['total'] += $file_data['meta']['count'];
			$queue_status['size']  += $file_data['meta']['size'];
		}

		return $queue_status;
	}
}
