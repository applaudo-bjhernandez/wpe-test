<?php

namespace DeliciousBrains\WPMDB\Common\Transfers\Files\Transport;

use DeliciousBrains\WPMDB\Common\Util\Util;
use WP_Error;
use WpOrg\Requests\Response;

class FileTransportResponse {
	/**
	 * @var bool
	 */
	public $success;

	/**
	 * @var string
	 */
	public $body;

	/**
	 * @var int
	 */
	public $code;

	/**
	 * @var mixed
	 */
	public $data;

	/**
	 * @var array
	 */
	public $decoded_body;

	/**
	 * @var Response
	 */
	public $response;

	/**
	 * @param Response $response
	 */
	public function __construct( $response ) {
		$this->response = $response;
		$this->code     = $response->status_code;
		$this->body     = $response->body;

		$this->set_decode_body();
		$this->set_success();
		$this->set_data();
	}

	/**
	 * Decode the JSON body.
	 */
	private function set_decode_body() {
		$this->decoded_body = json_decode( $this->body, true );
	}

	/**
	 * Set the success property based on the decoded body.
	 */
	private function set_success() {
		$this->success = ! empty( $this->decoded_body['success'] ) ? (bool) $this->decoded_body['success'] : false;
	}

	/**
	 * Set the data property based on the decoded body.
	 */
	private function set_data() {
		$this->data = ! empty( $this->decoded_body['data'] ) ? $this->decoded_body['data'] : null;
	}

	/**
	 * Returns true if the decoded response has a legacy wpmdb_error property.
	 *
	 * @return bool
	 */
	public function has_error() {
		return ! empty( $this->decoded_body['wpmdb_error'] );
	}

	/**
	 * Returns the msg property of the decoded legacy JSON error response.
	 *
	 * @return mixed|null
	 */
	public function error_message() {
		if ( $this->has_error() ) {
			if ( ! empty( $this->decoded_body['msg'] ) ) {
				return $this->decoded_body['msg'];
			}

			return sprintf( 'File transport failed with code %s', $this->code );
		}

		return null;
	}

	/**
	 * If there is a WP_Error in the response data, return the WP_Error.
	 *
	 * @return false|WP_Error
	 */
	public function get_wp_error() {
		if ( Util::array_is_wp_error( $this->data ) ) {
			$data = empty( $this->data['data'] ) ? null : $this->data['data'];

			return new WP_Error( $this->data['code'], $this->data['message'], $data );
		}

		return false;
	}

	/**
	 * Returns the response as a formatted array
	 *
	 * @return array
	 **/
	public function get_error_array() {
		return [
			'body'     => $this->body,
			'response' => [
				'code' => $this->code,
			],
		];
	}
}
