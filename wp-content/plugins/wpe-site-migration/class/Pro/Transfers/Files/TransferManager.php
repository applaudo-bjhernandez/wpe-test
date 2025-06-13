<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers\Files;

use DeliciousBrains\WPMDB\Common\FullSite\FullSiteExport;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Migration\MigrationHelper;
use DeliciousBrains\WPMDB\Common\Queue\Manager;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Chunker;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Transport\FileTransportResponse;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Transport\HTTP\CURLFileTransport;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Transport\HTTP\FileInBodyTransport;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Transport\TransportManager;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Data\Stage;
use DeliciousBrains\WPMDB\Pro\Transfers\Receiver;
use DeliciousBrains\WPMDB\Pro\Transfers\Sender;
use DeliciousBrains\WPMDB\Common\Transfers\Files\TransferManager as Common_TransferManager;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\Util\Util as Common_Util;
use Exception;
use WP_Error;

/**
 * Class TransferManager
 *
 * @package WPMDB\Transfers\Files
 */
class TransferManager extends Common_TransferManager {
	/**
	 * TransferManager constructor.
	 *
	 * @param $wpmdb
	 */

	/**
	 * @var Payload
	 */
	public $payload;

	/**
	 * @var Helper
	 */
	private $http_helper;

	/**
	 * @var Receiver
	 */
	private $receiver;

	/**
	 * @var Sender
	 */
	private $sender;

	/**
	 * @var SizeControllerInterface
	 */
	private $size_controller;

	/**
	 * @var TransportManager
	 */
	private $transport_manager;

	public function __construct(
		Manager $manager,
		Payload $payload,
		Util $util,
		SizeControllerInterface $size_controller,
		Helper $http_helper,
		Receiver $receiver,
		Sender $sender,
		FullSiteExport $full_site_export,
		TransportManager $transport_manager
	) {
		parent::__construct( $manager, $util, $full_site_export );
		$this->queueManager      = $manager;
		$this->payload           = $payload;
		$this->util              = $util;
		$this->http_helper       = $http_helper;
		$this->receiver          = $receiver;
		$this->sender            = $sender;
		$this->size_controller   = $size_controller;
		$this->transport_manager = $transport_manager;

		add_filter( 'wpmdb_transfers_payload_handle', [ $this, 'filter_tmpfile_handles' ] );
		add_filter( 'wpmdb_transfers_stream_handle', [ $this, 'filter_tmpfile_handles' ] );
	}

	/**
	 * @param array  $processed
	 * @param array  $state_data
	 * @param string $remote_url
	 *
	 * @return array|WP_Error
	 */
	public function handle_push( $processed, $state_data, $remote_url ) {
		// Set file transport methods
		$this->transport_manager->set_default_method( CURLFileTransport::class );
		$this->transport_manager->set_fallback_method( FileInBodyTransport::class );

		$transfer_max               = $state_data['site_details']['remote']['transfer_bottleneck'];
		$actual_bottleneck          = $state_data['site_details']['remote']['max_request_size'];
		$high_performance_transfers = $state_data['site_details']['remote']['high_performance_transfers'];

		// Register transport method
		$transport_method = $this->transport_manager->get_transport_method();

		if ( is_wp_error( $transport_method ) ) {
			return $transport_method;
		}

		$transport_method->register();

		// Calculate bottleneck
		$force_performance_transfers = apply_filters( 'wpmdb_force_high_performance_transfers', true, $state_data );

		$bottleneck            = apply_filters( 'wpmdb_transfers_push_bottleneck', $actual_bottleneck );
		$fallback_payload_size = 1000000;

		$bottleneck = $this->maybeUseHighPerformanceTransfers(
			$bottleneck,
			$high_performance_transfers,
			$force_performance_transfers,
			$transfer_max,
			$fallback_payload_size,
			$state_data
		);

		// Remove 1KB from the bottleneck as some hosts have a 1MB bottleneck
		$bottleneck -= 1000;

		$batch      = [];
		$total_size = 0;

		// Get subset of files to combine into a payload
		foreach ( $processed as $key => $file ) {
			$batch[] = $file;

			// This is a loose enforcement, actual payload size limit is implemented in Payload::create_payload()
			if ( ( $total_size + $file['size'] ) >= $bottleneck ) {
				break;
			}

			$total_size += $file['size'];
		}

		$payload = $this->payload->create_payload(
			$batch,
			$state_data,
			$bottleneck
		);

		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		if ( empty( $payload ) || ! is_array( $payload ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-payload_array_error',
				__( 'Could not create payload array for transfer.', 'wp-migrate-db' )
			);
		}

		list( $count, $sent, $handle, $chunked, $file, $chunk_data ) = $payload;

		$transfer_response = $this->attempt_post( $state_data, $remote_url, $handle );

		if ( is_wp_error( $transfer_response ) ) {
			return $transfer_response;
		}

		if ( 200 !== $transfer_response->code ) {
			return $this->util->fire_transfer_errors(
				sprintf(
					__( 'Payload transfer failed with code %1$s: %2$s', 'wp-migrate-db' ),
					$transfer_response->code,
					$transfer_response->body
				)
			);
		}

		// If we're not chunking
		if ( empty( $chunked ) ) {
			$this->queueManager->delete_data_from_queue( $count );
		}

		if ( ! empty( $chunked ) ) {
			$chunk_option_name = WPMDB_FILE_CHUNK_OPTION_PREFIX . $state_data['migration_state_id'];

			//chunking is complete, remove file(s) from queue and clean up the file chunk option
			if ( (int) $chunked['bytes_offset'] === $file['size'] ) {
				delete_site_option( $chunk_option_name );
				$file['chunking_done'] = true;

				$this->queueManager->delete_data_from_queue( $count );
			} else {
				// Record chunk data to DB for next iteration
				update_site_option( $chunk_option_name, $chunk_data );
			}
		}

		list( $total_sent, $sent_copy ) = $this->process_sent_data_push( $sent, $chunked );

		$result = [
			'total_transferred'     => $total_sent,
			'fallback_payload_size' => $fallback_payload_size,
		];

		if ( $this->canUseHighPerformanceTransfers( $high_performance_transfers, $force_performance_transfers ) ) {
			$result['current_payload_size']     = $this->size_controller->get_current_size();
			$result['reached_max_payload_size'] = $this->size_controller->is_at_max_size();
		}

		return $result;
	}

	/**
	 * @param array  $processed
	 * @param array  $state_data
	 * @param string $remote_url
	 *
	 * @return array|WP_Error
	 */
	public function handle_pull( $processed, $state_data, $remote_url ) {
		$transfer_max                = $this->util->get_transfer_bottleneck();
		$actual_bottleneck           = $state_data['site_details']['local']['max_request_size'];
		$high_performance_transfers  = $state_data['site_details']['local']['high_performance_transfers'];
		$force_performance_transfers = apply_filters( 'wpmdb_force_high_performance_transfers', true, $state_data );

		$bottleneck = apply_filters(
			'wpmdb_transfers_pull_bottleneck',
			$actual_bottleneck
		); //Use slider value

		$fallback_payload_size = 2500000;

		$bottleneck = $this->maybeUseHighPerformanceTransfers(
			$bottleneck,
			$high_performance_transfers,
			$force_performance_transfers,
			$transfer_max,
			$fallback_payload_size,
			$state_data
		);

		$batch      = [];
		$total_size = 0;
		$count      = 0;

		// Assign bottleneck to state data so remote can use it when assembling the payload
		$state_data['bottleneck'] = $bottleneck;

		foreach ( $processed as $key => $file ) {
			if ( $file['size'] > $bottleneck ) {
				$batch[] = $file;
				break;
			}

			$batch[] = $file;
			$count++;

			$total_size += $file['size'];
		}

		$stage = $state_data['stage'];
		$key   = $stage === Stage::MEDIA_FILES ? 'mf' : 'tp';

		try {
			list( $resp, $meta ) = $this->request_batch(
				base64_encode( str_rot13( json_encode( $batch ) ) ),
				$state_data,
				"wpmdb{$key}_transfers_send_file",
				$remote_url
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'wpmdb_error', $e->getMessage() );
		}

		if ( is_wp_error( $resp ) ) {
			return $resp;
		}

		//Delete data from queue
		$this->queueManager->delete_data_from_queue( $meta['count'] );

		$total_sent = 0;

		foreach ( $meta['sent'] as $sent ) {
			$total_sent += $sent['size'];
		}

		$result = [
			'total_transferred'     => $total_sent,
			'fallback_payload_size' => $fallback_payload_size,
		];

		if ( $this->canUseHighPerformanceTransfers( $high_performance_transfers, $force_performance_transfers ) ) {
			$result['current_payload_size']     = $this->size_controller->get_current_size();
			$result['reached_max_payload_size'] = $this->size_controller->is_at_max_size();
		}

		return $result;
	}

	/**
	 * Send a file payload to the remote.
	 *
	 * @param resource $payload
	 * @param array    $state_data
	 * @param string   $action
	 * @param string   $remote_url
	 *
	 * @return FileTransportResponse|WP_Error
	 * @throws Exception
	 */
	public function post( $payload, $state_data, $action, $remote_url ) {
		$sig_data = [
			'action'          => $action,
			'remote_state_id' => $state_data['migration_state_id'],
			'intent'          => $state_data['intent'],
			'stage'           => $state_data['stage'],
		];

		$state_data['sig'] = $this->http_helper->create_signature( $sig_data, $state_data['key'] );

		$state_data['action']          = $action;
		$state_data['remote_state_id'] = $state_data['migration_state_id'];
		$ajax_url                      = trailingslashit( $remote_url ) . 'wp-admin/admin-ajax.php';

		$response = $this->sender->post_payload( $payload, $state_data, $ajax_url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Handle legacy JSON error.
		if ( $response->has_error() ) {
			return new WP_Error( 'wpmdb-file_transfer-error', $response->error_message() );
		}

		// If the request failed, try to extract WP_Error data.
		if ( ! $response->success ) {
			$error = $response->get_wp_error();

			if ( is_wp_error( $error ) ) {
				return $error;
			}

			$error_data = Common_Util::format_additional_error_data(
				$response->get_error_array(),
				[ 'url' => $ajax_url, 'method' => 'POST' ]
			);

			if ( $response->code !== 200 ) {
				if ( strpos( $response->body, 'wordfence' ) !== false ) {
					return new WP_Error(
						'wpmdb-file_transfer-response-wordfence',
						Common_Util::get_wordfence_error_message(),
						$error_data
					);
				}

				return new WP_Error(
					'wpmdb-file_transfer-response',
					sprintf(
						__( 'File transfer failed with response code: %s', 'wp-migrate-db' ),
						$response->code
					),
					$error_data
				);
			}

			if ( $response->decoded_body === null ) {
				return new WP_Error(
					'wpmdb-file_transfer-body',
					sprintf(
						__( 'File transfer failed with response code: %s', 'wp-migrate-db' ),
						$response->code
					),
					$error_data
				);
			}

			// Handle a 200 response that was not successful.
			$fallback_msg = __( 'File transfer failed', 'wp-migrate-db' );
			$msg          = $response->data ? $response->data : $fallback_msg;

			return new WP_Error( 'wpmdb-file_transfer-failed', $msg );
		}

		// Returns response directly
		return $response;
	}

	/**
	 * @param string $batch
	 * @param array  $state_data
	 * @param string $action
	 * @param string $remote_url
	 *
	 * @return array|WP_Error
	 * @throws Exception
	 */
	public function request_batch( $batch, $state_data, $action, $remote_url ) {
		$data = [
			'action'          => $action,
			'remote_state_id' => MigrationHelper::get_current_migration_id(),
			'intent'          => $state_data['intent'],
			'stage'           => $state_data['stage'],
			'bottleneck'      => $state_data['bottleneck'],
		];

		$sig_data      = $data;
		$data['sig']   = $this->http_helper->create_signature( $sig_data, $state_data['key'] );
		$ajax_url      = trailingslashit( $remote_url ) . 'wp-admin/admin-ajax.php';
		$data['batch'] = $batch;

		try {
			$response = $this->receiver->send_request( $data, $ajax_url );
		} catch ( Exception $e ) {
			return new WP_Error( 'wpmdb_error', $e->getMessage() );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $this->receiver->receive_stream_batch( $response, $state_data );
	}

	/**
	 * @param array $sent
	 * @param array $chunked
	 *
	 * @return array
	 */
	public function process_sent_data_push( $sent, $chunked ) {
		$total_sent = 0;
		$filtered   = [];

		foreach ( $sent as $files_sent ) {
			$item_size = $files_sent['size'];

			if ( isset( $chunked['chunked'] ) && $chunked['chunked'] ) {
				$item_size = $chunked['chunk_size'];
			}

			$total_sent               += $item_size;
			$files_sent['chunk_size'] = $item_size;
			$filtered[]               = $files_sent;
		}

		return array( $total_sent, $filtered );
	}

	/**
	 * @param array    $state_data
	 * @param string   $remote_url
	 * @param resource $handle
	 *
	 * @return FileTransportResponse|WP_Error
	 */
	public function attempt_post( $state_data, $remote_url, $handle ) {
		if ( ! is_resource( $handle ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-invalid_resource_handle_error',
				__( 'Resource handle for payload is invalid.', 'wp-migrate-db' )
			);
		}

		rewind( $handle );
		$stage = $state_data['stage'];
		$key   = $stage === Stage::MEDIA_FILES ? 'mf' : 'tp';

		try {
			$transfer_status = $this->post( $handle, $state_data, "wpmdb{$key}_transfers_receive_file", $remote_url );
		} catch ( Exception $e ) {
			return new WP_Error( 'wpmdb_error', $e->getMessage() );
		}

		return $transfer_status;
	}

	/**
	 * Calculates the high performance mode bottleneck size.
	 *
	 * @param array $state_data
	 *
	 * @return int
	 */
	private function calculatePayLoadSize( $state_data ) {
		if ( ! isset( $state_data['stabilizePayloadSize'] ) || $state_data['stabilizePayloadSize'] !== true ) {
			if ( isset( $state_data['stepDownSize'] ) && $state_data['stepDownSize'] === true ) {
				//Discard existing chunk data to force a complete retry of the chunked file.
				$migration_id = MigrationHelper::get_current_migration_id();
				if ( ! empty( $migration_id ) ) {
					delete_site_option( Chunker::get_chunk_data_option_name( $migration_id ) );
				}

				return $this->size_controller->step_down_size( $state_data['retries'] );
			}

			return $this->size_controller->step_up_size();
		}

		return $this->size_controller->get_current_size();
	}

	/**
	 * If high performance mode can be used for current migration, the bottleneck value will be modified.
	 * Otherwise, the passed bottleneck will be returned unmodified.
	 *
	 * @param int   $bottleneck
	 * @param bool  $high_performance_transfers
	 * @param bool  $force_performance_transfers
	 * @param int   $transfer_max
	 * @param int   $fallback
	 * @param array $state_data
	 *
	 * @return int
	 */
	private function maybeUseHighPerformanceTransfers(
		$bottleneck,
		$high_performance_transfers,
		$force_performance_transfers,
		$transfer_max,
		$fallback,
		$state_data
	) {
		if ( $this->canUseHighPerformanceTransfers( $high_performance_transfers, $force_performance_transfers ) ) {
			// Get the payload size from the state data if it exists.
			$payload_size = isset( $state_data['payloadSize'] ) ? $state_data['payloadSize'] : null;

			// Did we switch the method? If so, start payload size from scratch.
			if ( $this->transport_manager->get_method_switched() ) {
				$state_data = Persistence::getStateData();

				// Also reset state parameters that control payload size.
				$payload_size = null;

				$state_data['stabilizePayloadSize'] = false;
				$state_data['stepDownSize']         = false;
				$state_data['attemptStepDown']      = false;
				$state_data['retries']              = 0;

				Persistence::saveStateData( $state_data );
			}

			$transfer_max = apply_filters(
				'wpmdb_high_performance_transfers_max_bottleneck',
				$transfer_max,
				$state_data['intent']
			);
			$this->size_controller->initialize(
				$transfer_max,
				$fallback,
				$payload_size
			);
			$bottleneck = apply_filters(
				'wpmdb_high_performance_transfers_bottleneck',
				$this->calculatePayLoadSize( $state_data ),
				$state_data['intent']
			);
		}

		return $bottleneck;
	}

	/**
	 * Checks if high performance mode can be enabled for current migration.
	 *
	 * @param bool $high_performance_transfers
	 * @param bool $force_performance_transfers
	 *
	 * @return bool
	 */
	private function canUseHighPerformanceTransfers( $high_performance_transfers, $force_performance_transfers ) {
		return ( true === $high_performance_transfers || true === $force_performance_transfers );
	}

	/**
	 * Filters the tmpfile handle, if it's empty it attempts to create another handle manually.
	 *
	 * @param resource|bool $handle
	 *
	 * @handles wpmdb_transfers_payload_handle
	 * @handles wpmdb_transfers_stream_handle
	 *
	 * @return resource
	 */
	public function filter_tmpfile_handles( $handle ) {
		if ( empty( $handle ) && function_exists( 'tempnam' ) && function_exists( 'sys_get_temp_dir' ) ) {
			//Attempt to create a temporary file manually
			$tmpfile = tempnam( sys_get_temp_dir(), 'mdb' );
			if ( false !== $tmpfile ) {
				$handle = fopen( $tmpfile, 'w+' );
			}
		}

		return $handle;
	}
}
