<?php

namespace DeliciousBrains\WPMDB\Common\Transfers\Files\Transport;

use DeliciousBrains\WPMDB\Common\Exceptions\UnknownStateProperty;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\CurrentMigrationState;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;
use DeliciousBrains\WPMDB\Common\Retry\RetryTrait;
use WP_Error;

class TransportManager {
	use RetryTrait;

	// Constants required by RetryTrait.
	const RETRY_ERROR_COUNT_PROPERTY       = 'last_retry_file_transport_error_count';
	const RETRY_ERROR_COUNT_TOTAL_PROPERTY = 'retry_file_transport_error_count_total';
	const RETRY_IN_PROGRESS_PROPERTY       = 'retry_file_transport_in_progress';
	const RETRY_COUNT_LIMIT                = 5;

	// Constants for this class.
	const TRANSPORT_METHOD_PROPERTY            = 'file_transport_method';
	const IS_USING_FALLBACK_METHOD_PROPERTY    = 'file_transport_is_using_fallback_method';
	const TRANSPORT_DEFAULT_METHOD_CONFIRMED   = 'file_transport_default_method_confirmed';
	const TRANSPORT_DEFAULT_METHOD_FAILED_SIZE = 'file_transport_default_method_failed_size';
	const TRANSPORT_FALLBACK_PROCESSED_SIZE    = 'file_transport_fallback_processed_size';

	// How much data should be processed by the fallback method before attempting to switch back to default.
	const FALLBACK_SAFETY_MARGIN = 3;

	/**
	 * @var TransportInterface
	 */
	private $default_transport;

	/**
	 * @var TransportInterface
	 */
	private $fallback_transport;

	/**
	 * @var bool
	 */
	private $method_switched = false;

	/**
	 * @var TransportInterface|WP_Error
	 */
	private $method;

	/**
	 * @var int
	 */
	private $payload_size = 0;

	/**
	 * Transports a file to destination using one of the set transport methods.
	 *
	 * @param resource    $payload_file
	 * @param array       $request_data
	 * @param string|null $url
	 * @param string|null $stage
	 *
	 * @return FileTransportResponse|WP_Error
	 */
	public function transport( $payload_file, $request_data = [], $url = null, $stage = null ) {
		$this->method = $this->get_transport_method();

		if ( is_wp_error( $this->method ) ) {
			return $this->method;
		}

		// If we're trying a different transport method, reset retry progress markers.
		if ( $this->get_method_switched() ) {
			self::reset_error_count();
			self::set_retry_in_progress( false );
		}

		$fstat              = fstat( $payload_file );
		$this->payload_size = $fstat['size'];

		$response = $this->method->transport( $payload_file, $request_data, $url );

		if ( is_wp_error( $response ) ) {
			// Set retry marker according to whether the error qualifies for retry, and we're able to retry.
			$retry = $this->handle_error( $response, $stage ) && self::should_retry( $response, $stage );
			self::set_retry_in_progress( $retry );
		} else {
			self::reset_error_count();
			self::set_retry_in_progress( false );
			$this->update_transport_method_tracking( true );
		}

		return $response;
	}

	/**
	 * Handles the errors returned from file transport attempts
	 * and updates the current_migration state with the error count to be used
	 * to determine the transport method in subsequent requests.
	 *
	 * @param WP_Error $error
	 * @param string   $stage
	 *
	 * @return bool whether the error qualifies for retry.
	 */
	protected function handle_error( $error, $stage ) {
		$error_message = $error->get_error_message();

		if ( empty( $error_message ) || ! is_string( $error_message ) ) {
			return false;
		}

		$fallback_errors = array_filter( $this->get_retry_errors(), function ( $item ) use ( $error_message ) {
			return strpos( trim( strtolower( $error_message ) ), trim( strtolower( $item ) ) ) !== false;
		} );

		// Does the error qualify for retry/fallback?
		if ( count( $fallback_errors ) > 0 ) {
			// Record the found errors in the migration stats.
			self::update_error_stats( $stage, $fallback_errors );

			// Increment the error count.
			self::increment_error_count();

			// Record that current transport method is currently unsuccessful.
			$this->update_transport_method_tracking( false );

			return true;
		}

		// We're not able to retry/fallback.
		return false;
	}

	/**
	 * Returns the transport method that should be used for transport.
	 *
	 * @return TransportInterface|WP_Error
	 */
	public function get_transport_method() {
		if ( $this->should_use_fallback_method() ) {
			$method = $this->fallback_transport;
		} else {
			$method = $this->default_transport;
		}

		$current_migration = StateFactory::create( 'current_migration' )->load_state( null );

		if ( is_a( $current_migration, CurrentMigrationState::class ) ) {
			$current_migration->set( self::TRANSPORT_METHOD_PROPERTY, $method->get_method_name(), false );
			$current_migration->update_state();
		}

		if ( empty( $method ) ) {
			return new WP_Error(
				'wpmdb_transport_manager',
				__( 'Unable to determine transport method, method is empty.', 'wp-migrate-db' )
			);
		}

		return $method;
	}

	/**
	 * Determines if the fallback method should be used.
	 *
	 * @return bool
	 */
	public function should_use_fallback_method() {
		if ( empty( $this->fallback_transport ) ) {
			return false;
		}

		$current_migration = StateFactory::create( 'current_migration' )->load_state( null );

		if ( is_a( $current_migration, CurrentMigrationState::class ) ) {
			try {
				$error_count = self::get_error_count( $current_migration );

				$is_using_fallback_method = ( bool ) $current_migration->get(
					self::IS_USING_FALLBACK_METHOD_PROPERTY,
					false
				);

				$default_method_confirmed = ( bool ) $current_migration->get(
					self::TRANSPORT_DEFAULT_METHOD_CONFIRMED,
					false
				);

				$default_method_failed_size = ( int ) $current_migration->get(
					self::TRANSPORT_DEFAULT_METHOD_FAILED_SIZE,
					false
				);

				$fallback_processed_size = ( int ) $current_migration->get(
					self::TRANSPORT_FALLBACK_PROCESSED_SIZE,
					false
				);

				// Are we using the fallback method already?
				if ( true === $is_using_fallback_method ) {
					/**
					 * How much data should be processed by the fallback method before attempting to switch back to
					 * default method? The default value is 3 meaning that we  will switch back to the default method
					 * when the fallback method has processed 3x the amount of data that the default method tried but
					 * failed to send.
					 *
					 * @param int $fallback_safety_margin Min allowed is 1, max allowed is 100.
					 */
					$fallback_safety_margin = (int) apply_filters(
						'wpmdb_transport_fallback_safety_margin',
						self::FALLBACK_SAFETY_MARGIN
					);

					$fallback_safety_margin = max( 1, min( 100, $fallback_safety_margin ) );

					// Switch back if default is known to be working and enough bytes were processed by fallback.
					if (
						$default_method_confirmed &&
						$fallback_processed_size > ( $default_method_failed_size * $fallback_safety_margin )
					) {
						// Set the flag to indicate that the fallback method is no longer being used.
						$current_migration->set( self::IS_USING_FALLBACK_METHOD_PROPERTY, false, false );
						$this->method_switched = true;

						// State was updated, let's save it.
						$current_migration->update_state();

						return false;
					}

					// Continue using the fallback.
					return true;
				}

				if ( self::RETRY_COUNT_LIMIT <= $error_count ) {
					//Set the flag to indicate that the fallback method is being used.
					$current_migration->set( self::IS_USING_FALLBACK_METHOD_PROPERTY, true, false );
					$this->method_switched = true;

					// State was updated, let's save it.
					$current_migration->update_state();

					return true;
				}

				return false;
			} catch ( UnknownStateProperty $exception ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Update the transport method stats in the current_migration state.
	 *
	 * @param bool $succeeded
	 *
	 * @return void
	 */
	private function update_transport_method_tracking( $succeeded ) {
		if ( $this->method instanceof WP_Error ) {
			return;
		}

		$current_migration = StateFactory::create( 'current_migration' )->load_state( null );

		if ( $current_migration instanceof CurrentMigrationState ) {
			$payload_size = $this->payload_size;

			switch ( $this->method->get_method_name() ) {
				case $this->default_transport->get_method_name():
					if ( $succeeded ) {
						// Set the default method as confirmed working.
						$current_migration->set(
							self::TRANSPORT_DEFAULT_METHOD_CONFIRMED,
							$this->default_transport->get_method_name(),
							false
						);
					} else {
						// Reset the byte counter for the fallback method.
						$current_migration->set( self::TRANSPORT_FALLBACK_PROCESSED_SIZE, 0, false );

						// Record the number of bytes we just failed to process.
						$current_migration->set( self::TRANSPORT_DEFAULT_METHOD_FAILED_SIZE, $payload_size, false );
					}
					break;
				case $this->fallback_transport->get_method_name():
					if ( $succeeded ) {
						// Increment the byte counter for the fallback method.
						$current_migration->inc( self::TRANSPORT_FALLBACK_PROCESSED_SIZE, $payload_size, false );
					}
					break;
			}

			$current_migration->update_state();
		}
	}

	/**
	 * Set the default transport method that will be used initially.
	 *
	 * @param string $method Transport method class name.
	 *
	 * @return void
	 */
	public function set_default_method( $method ) {
		$this->method_switched   = false;
		$this->default_transport = TransportFactory::create( $method );
	}

	/**
	 *
	 * Set the fallback transport method that will be used if the default fails too often.
	 *
	 * @param string $method Transport method class name.
	 *
	 * @return void
	 */
	public function set_fallback_method( $method ) {
		$this->method_switched    = false;
		$this->fallback_transport = TransportFactory::create( $method );
	}

	/**
	 * Did the transport method switch while setting up this transport attempt?
	 *
	 * @return bool
	 */
	public function get_method_switched() {
		return $this->method_switched;
	}
}
