<?php

namespace DeliciousBrains\WPMDB\Common\Compatibility\Layers\Themes;

abstract class AbstractTheme {
	/**
	 * The name of the theme as stated in the theme header.
	 *
	 * @var string
	 */
	protected static $name = '';

	/**
	 * Constructor - initialise the compatibility if the theme is active.
	 *
	 * @return void
	 */
	public function __construct() {
		if ( self::is_active() ) {
			$this->init();
		}
	}

	/**
	 * Returns true if the current theme is the one we are defining compatibility with.
	 *
	 * @return boolean
	 */
	public static function is_active() {
		$current_theme = wp_get_theme();

		return $current_theme->name === static::$name;
	}

	/**
	 * Initialise the compatibility.
	 *
	 * @return void
	 */
	abstract public function init();
}
