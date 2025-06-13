<?php

namespace DeliciousBrains\WPMDB\SiteMigration\TPF;

use DeliciousBrains\WPMDB\Common\Transfers\Files\Excludes;

class ThemePluginFilesAddon extends \DeliciousBrains\WPMDB\Common\TPF\ThemePluginFilesAddon {
	public function register() {
		parent::register();
		add_filter( 'wpmdb_excluded_plugins', [ $this, 'filter_excluded_plugins' ] );
		add_filter( 'wpmdb_filter_files_list', [ $this, 'filter_files_list' ], 10, 2 );
	}

	/**
	 * Filters the list of plugins
	 *
	 * @param array $plugins
	 *
	 * @return array
	 * @handles wpmdb_excluded_plugins
	 */
	public function filter_excluded_plugins( $plugins ) {
		//prevent WP Engine Site Migration from being included in the list of plugins
		$excluded_plugins = [ 'wpe-site-migration' ];

		if ( ! is_array( $plugins ) || empty( $plugins ) ) {
			$plugins = [];
		} else {
			// Core often excludes WP Migrate, but it's allowed here.
			$plugins = array_diff( $plugins, [ 'wp-migrate-db' ] );
		}

		return array_merge( $plugins, $excluded_plugins );
	}

	/**
	 * Filters the list of files that's displayed in different UI panels.
	 *
	 * @param array  $files
	 * @param string $stage_path
	 *
	 * @handles wpmdb_filter_files_list
	 * @return array
	 */
	public function filter_files_list( $files, $stage_path ) {
		if ( ! is_array( $files ) || empty( $files ) ) {
			return $files;
		}

		return array_filter( $files, function ( $file ) use ( $stage_path ) {
			$excludes = [ '.htaccess*' ];

			return ! Excludes::shouldExcludeFile( $file[0]['path'], $excludes, $stage_path );
		} );
	}
}
