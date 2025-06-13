<?php

namespace DeliciousBrains\WPMDB\SiteMigration\Files;

/**
 * Class Excludes
 *
 * @package WPMDB\SiteMigration\Files\Excludes
 * @see     WPMDB\Common\Transfers\Files for the lists of excludes these are filtered to
 */
class Excludes {
	const ALL_STAGES = [
		'.htaccess*',
	];

	const MEDIA_FILES = [
		'/pp/static/',
		'/siteground-optimizer-assets/',
		'/snapshots/',
		'/wp-clone/',
		'/wp-defender/',
	];

	const THEME_FILES = [];

	const PLUGIN_FILES = [
		'/bv-cloudways-automated-migration/',
		'/bv-migration-to-wpserveur/',
		'/bv-pantheon-migration/',
		'/db-cache-reloaded-fix/',
		'/dreamhost*',
		'/hyper-cache/',
		'/flywheel-migrations/',
		'/flywheel-automated-migration/',
		'/force-strong-passwords/',
		'/limit-login-attempts/',
		'/migrate-guru/',
		'/migrate-to-liquidweb/',
		'/migrate-to-wefoster/',
		'/nginx-helper/',
		'/pressable-automated-migration/',
		'/quick-cache/',
		'/quick-cache-pro/',
		'/savvii-wp-migrate/',
		'/siteground-migrator/',
		'/w3-total-cache/',
		'/wp-cache/',
		'/wp-file-cache/',
		'/wp-super-cache/',
		'/wp-site-migrate/',
	];

	const MUPLUGIN_FILES = [
		'/force-strong-passwords/',
		'/redis-cache-pro/',
		'/redis-cache-pro.php',
		'/slt-force-strong-passwords.php',
		'/stop-long-comments.php',
		'/wp-cache-memcached/',
	];

	const OTHER_FILES = [
		'/advanced-cache.php',
		'/bte-wb/',
		'/db.php',
		'/db-error.php',
		'/mysql.sql',
		'/object-cache.php',
		'/w3tc/',
		'/w3tc-config/',
		'/wp-cache-config.php',
	];


	const ROOT_FILES = [];

	public function register() {
		add_filter( 'wpmdb_all_stages_excludes', [ $this, 'filter_all_stages_excludes' ] );
		add_filter( 'wpmdb_media_files_excludes', [ $this, 'filter_media_files_excludes' ] );
		add_filter( 'wpmdb_theme_files_excludes', [ $this, 'filter_theme_files_excludes' ] );
		add_filter( 'wpmdb_plugin_files_excludes', [ $this, 'filter_plugin_files_excludes' ] );
		add_filter( 'wpmdb_muplugin_files_excludes', [ $this, 'filter_muplugin_files_excludes' ] );
		add_filter( 'wpmdb_other_files_excludes', [ $this, 'filter_other_files_excludes' ] );
		add_filter( 'wpmdb_root_files_excludes', [ $this, 'filter_root_files_excludes' ] );
	}

	/**
	 * Filters the list of excluded WPE files for all stages
	 *
	 * @param array $all_stages
	 *
	 * @return array
	 * @handles wpmdb_media_files_excludes
	 */
	public function filter_all_stages_excludes( $all_stages ) {
		return array_merge( $all_stages, self::ALL_STAGES );
	}

	/**
	 * Filters the list of excluded WPE media files
	 *
	 * @param array $media_files
	 *
	 * @return array
	 * @handles wpmdb_media_files_excludes
	 */
	public function filter_media_files_excludes( $media_files ) {
		return array_merge( $media_files, self::MEDIA_FILES );
	}

	/**
	 * Filters the list of excluded WPE theme files
	 *
	 * @param array $themes
	 *
	 * @return array
	 * @handles wpmdb_theme_files_excludes
	 */
	public function filter_theme_files_excludes( $themes ) {
		return array_merge( $themes, self::THEME_FILES );
	}

	/**
	 * Filters the list of excluded WPE plugin files
	 *
	 * @param array $files
	 *
	 * @return array
	 * @handles wpmdb_plugin_files_excludes
	 */
	public function filter_plugin_files_excludes( $plugins ) {
		return array_merge( $plugins, self::PLUGIN_FILES );
	}

	/**
	 * Filters the list of excluded WPE plugin files
	 *
	 * @param array $mu_plugins
	 *
	 * @return array
	 * @handles wpmdb_muplugin_files_excludes
	 */
	public function filter_muplugin_files_excludes( $mu_plugins ) {
		return array_merge( $mu_plugins, self::MUPLUGIN_FILES );
	}

	/**
	 * Filters the list of excluded WPE other files
	 *
	 * @param array $others
	 *
	 * @return array
	 * @handles wpmdb_other_files_excludes
	 */
	public function filter_other_files_excludes( $others ) {
		return array_merge( $others, self::OTHER_FILES );
	}

	/**
	 * Filters the list of excluded root files
	 *
	 * @param array $root_files
	 *
	 * @return array
	 * @handles wpmdb_root_files_excludes
	 */
	public function filter_root_files_excludes( $root_files ) {
		return array_merge( $root_files, self::ROOT_FILES );
	}
}
