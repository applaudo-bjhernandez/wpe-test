<?php

namespace DeliciousBrains\WPMDB\Common\Retry;

use DeliciousBrains\WPMDB\Common\Exceptions\UnknownStateProperty;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\CurrentMigrationState;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;
use WP_Error;

trait RetryTrait {
	/**
	 * A list of errors that are good candidates for retry,
	 * as they're generally network issues.
	 *
	 * The key is used for stats.
	 *
	 * @var string[]
	 */
	private $network_retry_errors = [
		'curl_error_28'           => 'cURL error 28',
		'curl_error_52'           => 'cURL error 52',
		'curl_error_56'           => 'cURL error 56',
		'internal_error_500'      => ': 500',
		'internal_error-500'      => '- 500',
		'service_unavailable_503' => ': 503',
		'service_unavailable-503' => '- 503',
		'gateway_timeout_504'     => ': 504',
		'gateway_timeout-504'     => '- 504',
		'error_520'               => ': 520',
		'error-520'               => '- 520',
		'error_521'               => ': 521',
		'error-521'               => '- 521',
	];

	/**
	 * Handles the returned errors and updates the current_migration state with
	 * the error count for the current stage.
	 *
	 * @param WP_Error $error
	 * @param string   $stage
	 *
	 * @return bool whether the error qualifies for retry.
	 */
	abstract protected function handle_error( $error, $stage );

	/**
	 * Get the error count from the current_migration state.
	 *
	 * @param CurrentMigrationState $current_migration Optional.
	 *
	 * @return int
	 */
	protected static function get_error_count( CurrentMigrationState $current_migration = null ) {
		if ( empty( $current_migration ) ) {
			$current_migration = StateFactory::create( 'current_migration' )->load_state( null );
		}

		if ( is_a( $current_migration, CurrentMigrationState::class ) ) {
			try {
				return (int) $current_migration->get( self::RETRY_ERROR_COUNT_PROPERTY );
			} catch ( UnknownStateProperty $exception ) {
				return 0;
			}
		}

		return 0;
	}

	/**
	 * Increment the error count in the current_migration state.
	 *
	 * @return void
	 */
	protected static function increment_error_count() {
		$current_migration = StateFactory::create( 'current_migration' )->load_state( null );

		if ( is_a( $current_migration, CurrentMigrationState::class ) ) {
			$count = self::get_error_count( $current_migration );
			$current_migration->set( self::RETRY_ERROR_COUNT_PROPERTY, $count + 1, false );
			$current_migration->update_state();
		}

		// As error count is reset on success, we also want to keep track of
		// total errors that could be retried.
		Persistence::incrementMigrationStat( self::RETRY_ERROR_COUNT_TOTAL_PROPERTY );
	}

	/**
	 * Resets the error count in the current_migration state.
	 *
	 * @return void
	 */
	protected static function reset_error_count() {
		$current_migration = StateFactory::create( 'current_migration' )->load_state( null );

		if ( is_a( $current_migration, CurrentMigrationState::class ) ) {
			$current_migration->set( self::RETRY_ERROR_COUNT_PROPERTY, 0, false );
			$current_migration->update_state();
		}
	}

	/**
	 * Updates the migration stats with the errors that occurred during the stage.
	 *
	 * @param string $stage  The stage of the migration.
	 * @param array  $errors The errors that occurred during the stage.
	 *
	 * @return bool
	 */
	protected static function update_error_stats( $stage, $errors ) {
		if ( ! is_array( $errors ) ) {
			return false;
		}

		foreach ( $errors as $key => $error ) {
			$stat_error = Persistence::getMigrationErrorFromStats( $stage, $key );

			if ( ! is_array( $stat_error ) || empty( $stat_error['error_count'] ) || ! is_numeric( $stat_error['error_count'] ) ) {
				$stat_error                = [];
				$stat_error['error_count'] = 1;
			} else {
				$stat_error['error_count'] += 1;
			}

			if ( empty( $stat_error['error_timestamps'] ) || ! is_array( $stat_error['error_timestamps'] ) ) {
				$stat_error['error_timestamps'] = [];
			}

			$stat_error['error_timestamps'][] = time();

			Persistence::addMigrationErrorToStats( $stage, $key, $stat_error );
		}

		return true;
	}

	/**
	 * Should we retry the failed item, or are we done with retrying?
	 *
	 * @param WP_Error $error
	 * @param string   $stage
	 *
	 * @return bool
	 */
	protected static function should_retry( $error, $stage ) {
		$error_count = self::get_error_count();

		// Save the last retry count property's value into migrations stats.
		// This will survive count reset.
		Persistence::setMigrationStat( self::RETRY_ERROR_COUNT_PROPERTY, $error_count );

		// Keep track of maximum number of retries that were reached.
		$max_error_count = Persistence::getMigrationStat( self::RETRY_ERROR_COUNT_PROPERTY . '_max' );

		if ( $error_count > $max_error_count ) {
			Persistence::setMigrationStat( self::RETRY_ERROR_COUNT_PROPERTY . '_max', $error_count );
		}

		if ( self::RETRY_COUNT_LIMIT <= $error_count ) {
			return false;
		}

		return true;
	}

	/**
	 * Get whether a retry is in progress from the current_migration state.
	 *
	 * @param CurrentMigrationState $current_migration Optional.
	 *
	 * @return bool
	 */
	public static function get_retry_in_progress( CurrentMigrationState $current_migration = null ) {
		if ( empty( $current_migration ) ) {
			$current_migration = StateFactory::create( 'current_migration' )->load_state( null );
		}

		if ( is_a( $current_migration, CurrentMigrationState::class ) ) {
			try {
				return (bool) $current_migration->get( self::RETRY_IN_PROGRESS_PROPERTY );
			} catch ( UnknownStateProperty $exception ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Set retry in progress marker on or off.
	 *
	 * @param bool $retry Is a retry allowed to happen?
	 *
	 * @return void
	 */
	protected static function set_retry_in_progress( $retry ) {
		$current_migration = StateFactory::create( 'current_migration' )->load_state( null );

		if ( is_a( $current_migration, CurrentMigrationState::class ) ) {
			$current_migration->set( self::RETRY_IN_PROGRESS_PROPERTY, (bool) $retry, false );
			$current_migration->update_state();
		}
	}

	/**
	 * If a retry is about to be processed, maybe sleep for a little while to
	 * give the remote a bit of breathing space.
	 *
	 * Each consecutive retry increases the wait time.
	 *
	 * @return void
	 */
	public static function maybe_wait_before_retry() {
		if ( ! self::get_retry_in_progress() ) {
			return;
		}

		$error_count = self::get_error_count();

		/**
		 * Filter the number of seconds for the 1st retry delay, and for which a
		 * multiplication factor is then applied for each subsequent retry.
		 *
		 * Can be a float, and multiplication factor is applied as float too,
		 * but the final value will be rounded up to the nearest whole second.
		 *
		 * Min value 1.0, max value 10.0.
		 *
		 * @param float $sleep_seconds Seconds to sleep, default 1.0.
		 */
		$sleep_seconds = max( min( (float) apply_filters( 'wpmdb_retry_sleep_base_seconds', 1 ), 10 ), 1 );
		$factor        = 1.0;

		if ( 1 < $error_count ) {
			/**
			 * Filter the multiplication factor applied to each subsequent retry delay.
			 *
			 * Can be a float, but the final value will be rounded up to the nearest whole second.
			 *
			 * Min value 1.0, max value 5.0.
			 *
			 * @param float $factor Default is 2, to double the wait time on each subsequent retry.
			 */
			$factor = max( min( (float) apply_filters( 'wpmdb_retry_sleep_multiplication_factor', 2 ), 5 ), 1 );

			for ( $i = 0; $i < $error_count; $i++ ) {
				$sleep_seconds = $sleep_seconds * $factor;
			}
		}

		sleep( ceil( $sleep_seconds ) );
	}

	/**
	 * Get an array of response errors that are considered relatively
	 * safe for retrying a request for, as they're primarily network issues.
	 *
	 * The key is used for stats.
	 *
	 * @return string[]
	 */
	protected function get_retry_errors() {
		return $this->network_retry_errors;
	}
}
