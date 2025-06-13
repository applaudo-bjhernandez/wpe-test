<?php

namespace DeliciousBrains\WPMDB\SiteMigration\Cli;

use DeliciousBrains\WPMDB\Common\Migration\MigrationHelper;
use DeliciousBrains\WPMDB\WPMDBDI;
use WP_CLI;

class Commands {

	public function __construct() {
		add_action( 'cli_init', [ $this, 'register' ] );
	}

	/**
	 * Register the command with WP-CLI.
	 */
	public static function register() {
		WP_CLI::add_command( 'migrate', self::class );
	}

	/**
	 * Output the connection info string.
	 *
	 * ## OPTIONS
	 * [--version]
	 * : Include the version in the connection info.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrate connection-info
	 *
	 */
	public function connection_info( $args, $assoc_args ) {
		$migration_helper = WPMDBDI::getInstance()->get( MigrationHelper::class );
		if ( is_a( $migration_helper, MigrationHelper::class ) ) {
			$connection_info = implode( ' ', $migration_helper->get_connection_info() );

			if ( is_array( $assoc_args ) && in_array( 'version', array_keys( $assoc_args ) ) ) {
				$connection_info .= ' ' . $this->version();
			}

			WP_CLI::log( $connection_info );
		}
	}

	/**
	 * Get the version of the plugin.
	 *
	 * @return string|null
	 */
	private function version() {
		if ( isset( $GLOBALS['wpmdb_meta']['wpe-site-migration']['version'] ) ) {
			return $GLOBALS['wpmdb_meta']['wpe-site-migration']['version'];
		}

		return null;
	}
}
