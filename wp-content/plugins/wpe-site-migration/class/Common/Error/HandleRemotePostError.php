<?php

namespace DeliciousBrains\WPMDB\Common\Error;

use WP_Error;

class HandleRemotePostError {
	/**
	 * Parse a remote post response for errors.
	 *
	 * If error found, return it, otherwise returns response.
	 *
	 * @param string $key          Error code to be used if the response is an error.
	 * @param mixed  $response     The response, usually JSON string or WP_Error.
	 * @param string $fallback_msg Used as error message if none in response.
	 *
	 * @return mixed|WP_Error
	 */
	public static function handle( $key, $response, $fallback_msg = '' ) {
		// WP_Error is thrown manually by remote_post() to tell us something went wrong.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$decoded_response = json_decode( $response, true );

		if ( false === $response || empty( $decoded_response['success'] ) ) {
			$msg = empty( $decoded_response['data'] ) && ! empty( $fallback_msg ) ? $fallback_msg : $decoded_response['data'];

			return new WP_Error(
				$key,
				$msg,
				$response
			);
		}

		if ( isset( $decoded_response['data'] ) ) {
			return $decoded_response['data'];
		}

		return $response;
	}
}
