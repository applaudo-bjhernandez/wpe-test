<?php

namespace DeliciousBrains\WPMDB\Common\Compatibility;

/**
 * Manages the theme compatibility classes.
 */
class ThemeCompatibilityManager {

	/**
	 * An internal list of theme compatibility manager objects that get created.
	 *
	 * @var array
	 */
	private $compatibility_classes = [];

	/**
	 * Loads each theme compatibility class and runs its constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// Load common theme compatibility classes.
		$this->load_theme_compatibility_classes(
			__DIR__ . '/Layers/Themes/*.php',
			'DeliciousBrains\WPMDB\Common\Compatibility\Layers\Themes\\'
		);

		// Load Pro-only theme compatibility classes.
		$this->load_theme_compatibility_classes(
			__DIR__ . '/../../Pro/Compatibility/Layers/Themes/*.php',
			'DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Themes\\'
		);
	}

	/**
	 * Loads theme compatibility classes from the specified path and using
	 * the specified namespace.
	 *
	 * Assumes a PSR-4-style directory and namespace structure where the directory
	 * path and namespace are related.
	 *
	 * @param string $path           The path to the files to load.
	 * @param string $base_namespace The base namespace for the classes. Should end with a backslash.
	 *
	 * @return void
	 */
	protected function load_theme_compatibility_classes( $path, $base_namespace ) {
		$theme_compatibility_files = glob( $path );

		if ( false === $theme_compatibility_files ) {
			return;
		}

		// Remove "AbstractTheme.php" from the list of files as it is the parent, abstract class.
		$theme_compatibility_files = array_filter( $theme_compatibility_files, function ( $file ) {
			return basename( $file ) !== 'AbstractTheme.php';
		} );

		foreach ( $theme_compatibility_files as $file ) {
			require_once $file;
			// Get the class name from the file name.
			$class_name = $base_namespace . basename( $file, '.php' );

			$this->compatibility_classes[ $class_name ] = new $class_name();
		}
	}
}
