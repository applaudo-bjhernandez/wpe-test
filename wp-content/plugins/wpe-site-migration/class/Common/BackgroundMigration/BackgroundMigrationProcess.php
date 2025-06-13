<?php

namespace DeliciousBrains\WPMDB\Common\BackgroundMigration;

use DeliciousBrains\WPMDB\Container\Brumann\Polyfill\Unserialize;
use DeliciousBrains\WPMDB\Common\Debug;
use DeliciousBrains\WPMDB\Common\Migration\InitiateMigration;
use DeliciousBrains\WPMDB\Common\Migration\MigrationManager;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\WPMDBDI;
use DeliciousBrains\WPMDB\Common\Util\Util;
use Exception;
use DeliciousBrains\WPMDB\Container\WP_Background_Process;
use WP_Error;

/**
 * Background Migration Process base class.
 */
abstract class BackgroundMigrationProcess extends WP_Background_Process {
	/**
	 * How many dispatches can be shutdown before assuming current background
	 * process chain is dead and healthcheck should be allowed to resume.
	 */
	const MIN_CHAIN_SHUTDOWNS     = 2;
	const MAX_CHAIN_SHUTDOWNS     = 20;
	const DEFAULT_CHAIN_SHUTDOWNS = 5;

	/**
	 * @inheritdoc
	 */
	protected $prefix = WPMDB_OPTION_PREFIX;

	/**
	 * @var BackgroundMigration
	 */
	private $migration;

	/**
	 * Was the task item handled ok?
	 *
	 * @var bool
	 */
	private $item_handled = false;

	/**
	 * The current phase being handled.
	 *
	 * @var string
	 */
	private $phase = 'initialization';

	public function __construct( BackgroundMigration $migration ) {
		parent::__construct( false );

		$this->migration = $migration;

		// Set a single cron schedule for all migration types as only one can be run at a time,
		// and so the background migration manager can set up the schedule early.
		$this->cron_interval_identifier = BackgroundMigrationManager::CRON_INTERVAL_IDENTIFIER;

		// We don't need to update the cron schedules for each background process as the manager has it sorted.
		remove_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );

		// Set process lock duration to longer than a minimum cron interval of 60 seconds.
		add_filter( $this->identifier . '_queue_lock_time', function ( $seconds ) {
			return 90;
		} );

		// Process lifecycle hooks to monitor chain.
		add_filter( $this->identifier . '_pre_dispatch', array( $this, 'filter_pre_dispatch' ), 10, 2 );
		add_action( $this->identifier . '_process_locked', array( $this, 'handle_process_locked' ), 10, 4 );
		add_action( 'wpmdb_migration_starting', array( $this, 'handle_remove_current_chain_info' ) );
		add_action( $this->identifier . '_paused', array( $this, 'handle_remove_current_chain_info' ) );
		add_action( $this->identifier . '_completed', array( $this, 'handle_remove_current_chain_info' ) );
		add_action( $this->identifier . '_cancelled', array( $this, 'handle_remove_current_chain_info' ) );
	}

	/**
	 * Schedule the cron healthcheck and dispatch an async request to start processing the queue.
	 *
	 * @handles {identifier}_pre_dispatch
	 *
	 * @param bool   $cancel   Should the dispatch be cancelled?
	 * @param string $chain_id The background process chain ID.
	 *
	 * @return bool
	 */
	public function filter_pre_dispatch( $cancel, $chain_id ) {
		if ( $cancel ) {
			return $cancel;
		}

		// We need to make sure we get latest chain info from db, not from process's cache.
		// At this point the process already has its coat on and is halfway out the door,
		// so there's no real performance hit by doing this.
		wp_cache_flush();

		$chain_info = get_site_option( $this->identifier . '_current_chain_info' );

		$current_chain_id = '';
		$shutdowns        = 0;
		if ( is_array( $chain_info ) && ! empty( $chain_info['chain_id'] ) && isset( $chain_info['shutdowns'] ) ) {
			$current_chain_id = $chain_info['chain_id'];
			$shutdowns        = (int) $chain_info['shutdowns'];
		}

		Debug::log( __FUNCTION__ . ': Chain ID: ' . $chain_id );
		Debug::log( __FUNCTION__ . ': Current Chain ID: ' . $current_chain_id );
		Debug::log( __FUNCTION__ . ': Shutdowns: ' . $shutdowns );

		// If we've been gazumped, shutdown this background process chain.
		if ( ! empty( $current_chain_id ) && $current_chain_id !== $chain_id ) {
			Persistence::incrementMigrationStat( 'background_dispatch_duplicate_count' );

			$allowed_shutdowns = min(
				max(
					apply_filters( 'wpmdb_allowed_dispatch_shutdowns', self::DEFAULT_CHAIN_SHUTDOWNS ),
					self::MIN_CHAIN_SHUTDOWNS
				),
				self::MAX_CHAIN_SHUTDOWNS
			);
			Debug::log( __FUNCTION__ . ': Allowed Shutdowns: ' . $allowed_shutdowns );

			// If pause or cancel requested, we do not want to accidentally resurrect
			// processing by hitting the shutdowns limit, even for returning zombies.
			// When pause or cancel is requested, the current chain info should eventually
			// clean itself up when the current chain comes back round for re-dispatch.
			if ( $this->is_paused() || $this->is_cancelled() ) {
				Debug::log( __FUNCTION__ . ': Resetting shutdowns as paused or cancelled.' );
				$shutdowns = 0;
			} else {
				$shutdowns++;
			}

			if ( $shutdowns <= $allowed_shutdowns ) {
				$chain_info['shutdowns'] = $shutdowns;

				// Ensure chain info is properly updated in db.
				delete_site_option( $this->identifier . '_current_chain_info' );
				update_site_option(
					$this->identifier . '_current_chain_info',
					$chain_info
				);

				Debug::log( __FUNCTION__ . ': --- SHUTTING DOWN CHAIN ID: ' . $chain_id . ' ---' );
				Persistence::incrementMigrationStat( 'background_dispatch_duplicate_shutdown_count' );

				return true;
			}

			Debug::log( __FUNCTION__ . ': +++ ALLOWING CHAIN ID: ' . $chain_id . ' TO DISPATCH +++' );
			Persistence::incrementMigrationStat( 'background_dispatch_duplicate_allowed_count' );
		}

		Debug::log( __FUNCTION__ . ': %%% Dispatching with Chain ID: ' . $chain_id . ' %%%' );
		Persistence::incrementMigrationStat( 'background_dispatch_count' );

		return $cancel;
	}

	/**
	 * Handles action to note whether the background process managed to create its lock.
	 *
	 * @handles {identifier}_process_locked
	 *
	 * @param bool   $locked        Whether the lock was successfully created.
	 * @param string $microtime     Microtime string value used for the lock.
	 * @param int    $lock_duration Max number of seconds that the lock will live for.
	 * @param string $chain_id      Current background process chain ID.
	 */
	public function handle_process_locked( $locked, $microtime, $lock_duration, $chain_id ) {
		// If this process got to the point of getting a process lock,
		// then it's going to start running the task and should be set as
		// the current chain.
		if ( $locked ) {
			// Update indicator of current background process chain ID in db,
			// and zero out competing dispatches.
			delete_site_option( $this->identifier . '_current_chain_info' );
			update_site_option(
				$this->identifier . '_current_chain_info',
				[ 'chain_id' => $chain_id, 'shutdowns' => 0 ]
			);
			Debug::log( __FUNCTION__ . ': *** New lock for Chain ID: ' . $chain_id . ' ***' );
		}
	}

	/**
	 * Handles action that results in needing a new background process chain.
	 *
	 * @handles wpmdb_migration_starting
	 * @handles {identifier}_paused
	 * @handles {identifier}_completed
	 * @handles {identifier}_cancelled
	 */
	public function handle_remove_current_chain_info() {
		// Clear out old indicator of current background process chain ID.
		delete_site_option( $this->identifier . '_current_chain_info' );
		Debug::log( __FUNCTION__ . ': ~~~ Removed current chain info. ~~~' );
	}

	/**
	 * @inheritDoc
	 */
	protected function task( $item ) {
		// Ensure that shutdown handler knows that current item has not been handled yet.
		$this->item_handled = false;

		// Umm, something funny is going on, bail.
		if ( ! is_array( $item ) || ! isset( $item['initialized'] ) ) {
			return $this->task_item( false );
		}

		// A background migration must have an associated Migration ID.
		if ( empty( $item['migration_id'] ) || ! $this->migration->set_current_migration_state( $item['migration_id'] ) ) {
			return $this->task_item( false );
		}

		// Do initial calculations on first spawn.
		if ( empty( $item['initialized'] ) ) {
			// If there's a fatal error, catch it.
			do_action( 'wpmdb_register_background_shutdown_handler', array( $this, 'shutdown_handler' ), $item );

			$item = $this->initialize_stages( $item );

			// Done catching fatal errors for now.
			do_action( 'wpmdb_unregister_background_shutdown_handler' );

			if ( static::all_stages_processed( $item ) ) {
				// Nothing to do, remove from queue.
				return $this->task_item( false );
			} else {
				return $this->task_item( $item );
			}
		}

		if ( static::all_stages_processed( $item ) ) {
			// Nothing to do, remove from queue.
			return $this->task_item( false );
		}

		// If there's a fatal error, catch it.
		do_action( 'wpmdb_register_background_shutdown_handler', array( $this, 'shutdown_handler' ), $item );

		$item = $this->task_item( $this->process_stages( $item ) );

		// Done catching fatal errors for now.
		do_action( 'wpmdb_unregister_background_shutdown_handler' );

		return $item;
	}

	/**
	 * A task item value is about to be updated.
	 *
	 * @param array|bool|WP_Error $item
	 *
	 * @return array|bool|WP_Error
	 */
	private function task_item( $item ) {
		/**
		 * A task item value is about to be updated.
		 *
		 * @param array|bool|WP_Error $item       The background migration batch item.
		 * @param BackgroundMigration $migration  The background migration.
		 * @param string              $identifier The background migration process identifier.
		 */
		$item = apply_filters(
			'wpmdb_task_item',
			/**
			 * A task item value is about to be updated.
			 *
			 * This is a more targeted version of the wpmdb_task_item filter,
			 * for when you know you only want to handle a particular migration type's
			 * batch item updates.
			 *
			 * @param array|bool|WP_Error $item      The background migration batch item.
			 * @param BackgroundMigration $migration The background migration.
			 */
			apply_filters( $this->identifier . '_task_item', $item, $this->migration ),
			$this->migration,
			$this->identifier
		);

		// Ensure shutdown handler knows we're ok.
		$this->item_handled = true;

		if ( self::has_errors( $item ) ) {
			// Soft cancel without in-built dispatch so that item can be returned properly.
			update_site_option( $this->get_status_key(), self::STATUS_CANCELLED );

			do_action( 'wpmdb_track_migration_error', self::get_error( $item ) );
			do_action( 'wpmdb_migration_failed', $item['migration_id'] );
		}

		return $item;
	}

	/**
	 * If the background process is exiting without having handled the item, log the issue.
	 *
	 * @param array $item being handled when callback set up.
	 *
	 * @return void
	 */
	public function shutdown_handler( $item ) {
		// Try and log the error message for display.
		if ( ! $this->item_handled ) {
			$error = error_get_last();

			if ( ! empty( $error['message'] ) && ! empty( $item['stages'] ) ) {
				$complete_key = 'processing' === $this->phase ? 'processed' : 'initialized';

				// Find the earliest incomplete stage and add error to it.
				foreach ( $item['stages'] as &$stage ) {
					if ( empty( $stage[ $complete_key ] ) ) {
						self::add_error_to_stage(
							new WP_Error( 'fatal-error-' . $this->phase, $error['message'] ),
							$stage
						);
						break;
					}
				}

				// Update the batch record as that's not going to happen otherwise.
				$batch = $this->get_batch();

				// We only add a single item to a batch, so can assume 1st is current item.
				foreach ( $batch->data as $key => $value ) {
					$batch->data[ $key ] = $this->task_item( $item );
					$this->update( $batch->key, $batch->data );
					break;
				}
			}

			// Regardless of whether we could save an error message, force-cancel processing.
			$this->unlock_process();
			$this->cancel();
		}
	}

	/**
	 * Set up target totals.
	 *
	 * @param array $item
	 *
	 * @return array|false
	 */
	protected function initialize_stages( $item ) {
		if ( ! is_array( $item ) || ! isset( $item['initialized'] ) ) {
			return false;
		}

		// Just in case.
		if ( ! empty( $item['initialized'] ) ) {
			return $item;
		}

		$num_stages = count( $item['stages'] );
		$stage      = null;
		$stage_num  = 0;

		// Iterate over stages and calculate stage and accumulated total target bytes.
		foreach ( $item['stages'] as &$stage ) {
			$stage_num++;

			// Don't re-initialize if later stage didn't complete and needs another go-around.
			if ( ! empty( $stage['initialized'] ) ) {
				continue;
			}

			// Only try and process data if not pausing or cancelling.
			if ( $this->is_paused() || $this->is_cancelled() ) {
				return $item;
			}

			// Useful for debugging and performance analysis.
			$stage['initialization_started_at'] = empty( $stage['initialization_started_at'] ) ? time() : $stage['initialization_started_at'];

			$initialized_bytes = $stage['total']['target_bytes'];
			$stage             = $this->initialize_stage( $stage );

			// Update target_bytes.
			$item['total']['target_bytes'] += $stage['total']['target_bytes'] - $initialized_bytes;

			// Useful for debugging and performance analysis.
			if ( ! empty( $stage['initialized'] ) ) {
				$stage['initialization_finished_at'] = time();
			}

			// We did something, let the UI see how much.
			break;
		}

		// If last stage initialized, mark initialization as complete.
		if ( $stage_num >= $num_stages && $stage['initialized'] ) {
			$item['initialized']                = true;
			$item['initialization_finished_at'] = time();
		}

		return $item;
	}

	/**
	 * Have all stages been processed?
	 *
	 * @param array $item
	 *
	 * @return bool
	 */
	public static function all_stages_processed( $item ) {
		if ( ! is_array( $item ) || empty( $item['stages'] ) ) {
			return true;
		}

		foreach ( $item['stages'] as $stage ) {
			if ( empty( $stage['processed'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get integer array element index for the stage that is currently initializing or processing.
	 *
	 * @param array $item
	 *
	 * @return int|false
	 */
	public static function current_stage( $item ) {
		if ( empty( $item ) || ! is_array( $item ) || empty( $item['stages'] ) ) {
			return false;
		}

		$complete_key = empty( $item['initialized'] ) ? 'initialized' : 'processed';

		foreach ( $item['stages'] as $idx => $stage ) {
			if ( empty( $stage[ $complete_key ] ) && ! empty( $stage['stage'] ) ) {
				return $idx;
			}
		}

		return false;
	}

	/**
	 * Get first error array in item, if it exists.
	 *
	 * @param array $item
	 *
	 * @return array|bool
	 */
	public static function get_error( $item ) {
		if ( ! is_array( $item ) || empty( $item['stages'] ) ) {
			return false;
		}

		foreach ( $item['stages'] as $stage ) {
			if ( ! empty( $stage['errors'][0] ) && is_array( $stage['errors'][0] ) ) {
				return $stage['errors'][0];
			}
		}

		return false;
	}

	/**
	 * Are there any errors?
	 *
	 * @param array $item
	 *
	 * @return bool
	 */
	public static function has_errors( $item ) {
		return ! empty( self::get_error( $item ) );
	}

	/**
	 * Add the given error to the item.
	 *
	 * @param WP_Error $wp_error
	 * @param array    $item Passed by ref.
	 */
	public static function add_error_to_item( WP_Error $wp_error, &$item ) {
		if ( empty( $item ) || ! is_array( $item ) || empty( $item['stages'] ) ) {
			return;
		}

		$stage_idx = static::current_stage( $item );

		if ( false !== $stage_idx && is_int( $stage_idx ) && ! empty( $item['stages'][ $stage_idx ] ) ) {
			self::add_error_to_stage( $wp_error, $item['stages'][ $stage_idx ] );
		}
	}

	/**
	 * Add the given error to the stage.
	 *
	 * @param WP_Error $wp_error
	 * @param array    $stage Passed by ref.
	 */
	private static function add_error_to_stage( WP_Error $wp_error, &$stage ) {
		$error             = [
			'code'    => $wp_error->get_error_code(),
			'message' => $wp_error->get_error_message(),
			'data'    => $wp_error->get_error_data(),
		];
		$error             = Util::strip_tags_from_array( $error );
		$stage['errors'][] = $error;
	}

	/**
	 * Process the stages of an item, keeping processed state up to date.
	 *
	 * @param array $item
	 *
	 * @return array|false
	 */
	protected function process_stages( $item ) {
		$this->phase = 'processing';

		if ( ! is_array( $item ) || empty( $item['stages'] ) ) {
			return false;
		}

		// Only try and process data if not pausing or cancelling.
		if ( $this->is_paused() || $this->is_cancelled() ) {
			return $item;
		}

		foreach ( $item['stages'] as &$stage ) {
			if ( ! empty( $stage['processed'] ) ) {
				continue;
			}

			// Useful for debugging and performance analysis.
			$stage['processing_started_at'] = empty( $stage['processing_started_at'] ) ? time() : $stage['processing_started_at'];

			// Process stage for a while and hopefully set processed if it is completed.
			$processed_bytes = $stage['total']['processed_bytes'];
			$stage           = $this->process_stage( $stage, $item );

			// Update processed bytes in grand total.
			if ( $processed_bytes < $stage['total']['processed_bytes'] ) {
				$item['total']['processed_bytes'] += $stage['total']['processed_bytes'] - $processed_bytes;
			}

			// Useful for debugging and performance analysis.
			if ( ! empty( $stage['processed'] ) ) {
				$stage['processing_finished_at'] = time();
			}

			// We've done some processing, let parent decide whether we should do some more.
			break;
		}

		// Just in case.
		if ( $item['total']['processed_bytes'] > $item['total']['target_bytes'] ) {
			$item['total']['processed_bytes'] = $item['total']['target_bytes'];
		}

		return $item;
	}

	/**
	 * Get total number of bytes expected to be processed for given stage.
	 *
	 * May be overridden by subclass, e.g. pull may calculate differently than push.
	 *
	 * @param array $stage
	 *
	 * @return array Number of initialized bytes, and an indicator that initialization is complete.
	 */
	protected function initialize_stage( $stage ) {
		try {
			$progress = WPMDBDI::getInstance()->get( InitiateMigration::class )->enqueue_stage( $stage['stage'] );
		} catch ( Exception $e ) {
			$progress = new WP_Error( $e->getCode(), $e->getMessage() );
		}

		// If there is an error, log it and stop processing.
		if ( is_wp_error( $progress ) ) {
			self::add_error_to_stage( $progress, $stage );

			return $stage;
		}

		// Update stage target bytes and initialization status.
		if ( ! empty( $progress['initialized_bytes'] ) && is_numeric( $progress['initialized_bytes'] ) && 0 < $progress['initialized_bytes'] ) {
			$stage['total']['target_bytes'] = $progress['initialized_bytes'];
		}

		$stage['initialized'] = ! empty( $progress['complete'] ) ? (bool) $progress['complete'] : false;

		return $stage;
	}

	/**
	 * Process the given stage.
	 *
	 * @param array $stage
	 * @param array $item
	 *
	 * @return array
	 */
	protected function process_stage( $stage, $item ) {
		try {
			// Call stage specific functionality to progress migration.
			$progress = WPMDBDI::getInstance()->get( MigrationManager::class )->process_stage(
				$stage['stage'],
				$item['migration_id']
			);
		} catch ( Exception $e ) {
			$progress = new WP_Error( $e->getCode(), $e->getMessage() );
		}

		// If there is an error, log it and stop processing.
		if ( is_wp_error( $progress ) ) {
			self::add_error_to_stage( $progress, $stage );

			return $stage;
		}

		// Update stage's processed bytes according to what's returned from slice of processing.
		if ( ! empty( $progress['processed_bytes'] ) && is_numeric( $progress['processed_bytes'] ) && 0 < $progress['processed_bytes'] ) {
			$stage['total']['processed_bytes'] += $progress['processed_bytes'];
		}

		// Just in case.
		if ( $stage['total']['processed_bytes'] > $stage['total']['target_bytes'] ) {
			$stage['total']['processed_bytes'] = $stage['total']['target_bytes'];
		}

		/**
		 * Potentially set stage as processed.
		 *
		 * @param bool  $processed
		 * @param array $progress data from last slice of processing, has values for 'processed_bytes' and 'complete'.
		 * @param array $stage
		 * @param array $item
		 *
		 * @returns bool
		 */
		$stage['processed'] = apply_filters(
			'wpmdb_stage_processed',
			$this->stage_processed( $progress, $stage, $item ),
			$progress,
			$stage,
			$item
		);

		return $stage;
	}

	/**
	 * Has the stage been fully processed?
	 *
	 * This function should be overridden by each subclass.
	 *
	 * Depending on status returned from stage processing, potentially complete stage,
	 * and maybe pause if user interaction needed.
	 * This is the last step in handling a stage's processing so that this can be implemented in a subclass.
	 *
	 * By default, if stage's progress 'complete' value is true, then the stage is considered processed.
	 *
	 * @param array $progress data from last slice of processing, has values for 'processed_bytes' and 'complete'.
	 * @param array $stage
	 * @param array $item
	 *
	 * @return bool
	 */
	protected function stage_processed( $progress, $stage, $item ) {
		return ! empty( $progress['complete'] );
	}

	/**
	 * Does the current migration require a preview before finalize?
	 *
	 * @return bool
	 */
	protected function preview() {
		return (bool) $this->migration->get_current_migration_state()->get( 'preview' );
	}

	/**
	 * Called when background process has been cancelled.
	 */
	protected function cancelled() {
		parent::cancelled();

		WPMDBDI::getInstance()->get( MigrationManager::class )->cancel_migration( [ 'action' => 'cancel' ] );

		do_action( 'wpmdb_migration_canceled' );
	}

	/**
	 * Called when background process has completed.
	 */
	protected function completed() {
		parent::completed();

		$migration_id = $this->migration->get_current_migration_state()->get( 'migration_id', false );

		// Ensure duplicate background process does not accidentally say
		// migration is complete when message already sent or another state
		// change removed batch data before task returned.
		if ( empty( $migration_id ) ) {
			Debug::log( __FUNCTION__ . ': Completed Chain ID ' . $this->get_chain_id() . ' after migration stopped.' );

			return;
		}

		do_action(
			'wpmdb_migration_completed',
			$migration_id
		);
	}

	/**
	 * Handle cron healthcheck event.
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		Persistence::incrementMigrationStat( 'healthcheck_run_count' );

		if ( $this->is_processing() ) {
			// Background process already running.
			exit;
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();
			exit;
		}

		Persistence::incrementMigrationStat( 'healthcheck_resume_count' );

		$this->dispatch();
	}

	/**
	 * Maybe unserialize data, but not if an object.
	 *
	 * @param mixed      $data            Data to be unserialized.
	 * @param bool|array $allowed_classes Array of class names that can be unserialized.
	 *
	 * @return mixed
	 */
	protected static function maybe_unserialize( $data, $allowed_classes ) {
		if ( is_serialized( $data ) ) {
			$options = array();
			if ( is_bool( $allowed_classes ) || is_array( $allowed_classes ) ) {
				$options['allowed_classes'] = $allowed_classes;
			}

			return Unserialize::unserialize( $data, $options );
		}

		return $data;
	}
}
