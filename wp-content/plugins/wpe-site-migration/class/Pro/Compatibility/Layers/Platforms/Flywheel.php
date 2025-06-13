<?php

namespace DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Platforms;

class Flywheel extends AbstractPlatform {
	/**
	 * @var string
	 */
	protected static $key = 'flywheel';

	public function __construct() {
		parent::__construct();

		add_filter( 'wpmdb_get_root_stage_base_dir', [ $this, 'filter_get_root_stage_base_dir' ] );
		add_filter( 'wpmdb_get_root_files_stage_base_dir', [ $this, 'filter_get_root_stage_base_dir' ] );
	}

	/**
	 * Are we running on this platform?
	 *
	 * @return bool
	 */
	public static function is_platform() {
		return defined( 'FLYWHEEL_CONFIG_DIR' );
	}

	/**
	 * Filters the current platform key.
	 *
	 * @param string $platform
	 *
	 * @return string
	 */
	public function filter_platform( $platform ) {
		if ( static::is_platform() ) {
			return static::get_key();
		}

		return $platform;
	}

	/**
	 * Gets the root files directory for the platform.
	 *
	 * @param string $directory
	 *
	 * @return string
	 */
	public function filter_get_root_stage_base_dir( $directory ) {
		if ( static::is_platform() && defined( 'FLYWHEEL_CONFIG_DIR' ) ) {
			return str_replace( '/flywheel-config', '', FLYWHEEL_CONFIG_DIR );
		}

		return $directory;
	}
}
