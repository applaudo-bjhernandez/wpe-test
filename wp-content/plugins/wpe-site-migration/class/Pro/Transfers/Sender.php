<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers;

use DeliciousBrains\WPMDB\Common\Transfers\Files\Transport\FileTransportResponse;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Transport\TransportManager;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Payload;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use Exception;
use WP_Error;

class Sender {
	static $end_sig           = '###WPMDB_EOF###';
	static $end_bucket        = '###WPMDB_END_BUCKET###';
	static $start_payload     = '###WPMDB_PAYLOAD###';
	static $end_payload       = '###WPMDB_END_PAYLOAD###';
	static $start_meta        = '###WPMDB_START_META###';
	static $end_meta          = '###WPMDB_END_META###';
	static $start_bucket_meta = '####WPMDB_BUCKET_META####';
	static $end_bucket_meta   = '###WPMDB_END_BUCKET_META###';

	public $util;
	public $payload;

	/**
	 * @var TransportManager
	 */
	private $transport_manager;

	/**
	 * Sender constructor.
	 *
	 * @param Util    $util
	 * @param Payload $payload
	 */
	public function __construct(
		Util $util,
		Payload $payload,
		TransportManager $transport_manager
	) {
		$this->util              = $util;
		$this->payload           = $payload;
		$this->transport_manager = $transport_manager;
	}

	/**
	 * Transport payload to remote site
	 *
	 * @param resource $payload
	 * @param string   $url
	 *
	 * @return FileTransportResponse|WP_Error
	 */
	public function post_payload( $payload, $state_data, $url = '' ) {
		// Encode state data as json to prevent issues with CURL
		$encoded_state_data = json_encode( $state_data );

		if ( false === $encoded_state_data ) {
			return new WP_Error(
				'wpmdb_files_sender',
				__( 'Unable to JSON encode state data for payload transport.', 'wp-migrate-db' )
			);
		}

		$payload_data = [
			'state_data' => base64_encode( $encoded_state_data ),
			'action'     => $state_data['action'],
		];

		return $this->transport_manager->transport( $payload, $payload_data, $url, $state_data['stage'] );
	}

	/**
	 * Receives pushed files.
	 *
	 * @param array $state_data
	 *
	 * @return null|WP_Error
	 */
	public function respond_to_send_file( $state_data ) {
		if ( ! isset( $_POST['batch'] ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-empty_batch_error',
				__( '$_POST[\'batch\'] is empty.', 'wp-migrate-db' )
			);
		}

		$batch = filter_var( $_POST['batch'], FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$batch = json_decode( str_rot13( base64_decode( $batch ) ), true );

		if ( ! $batch || ! \is_array( $batch ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-batch_request_error',
				__( 'Request for batch of files failed.', 'wp-migrate-db' )
			);
		}

		$handle = $this->payload->create_payload( $batch, $state_data, $state_data['bottleneck'] );

		if ( is_wp_error( $handle ) ) {
			return $handle;
		}

		if ( ! is_resource( $handle ) ) {
			return new WP_Error(
				'wpmdb-file_transfer-resource_handle_error',
				__( 'Could not create resource handle for payload.', 'wp-migrate-db' )
			);
		}

		rewind( $handle );

		// Read payload line by line and send each line to the output buffer
		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, 10 * 10 * 10000 );
			echo $buffer;

			@ob_flush();
			flush();
		}

		fclose( $handle );

		return null;
	}

	protected function print_end() {
		echo "\n" . static::$end_sig;
	}
}
