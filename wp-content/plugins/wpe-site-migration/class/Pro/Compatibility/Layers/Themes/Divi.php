<?php

namespace DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Themes;

use DeliciousBrains\WPMDB\Common\Compatibility\Layers\Themes\AbstractTheme;

/**
 * Theme compatibility for Divi.
 *
 * Will be loaded from the common ThemeCompatibilityManager.
 */
class Divi extends AbstractTheme {
	/**
	 * The name of the theme as stated in the theme header.
	 *
	 * @var string
	 */
	protected static $name = 'Divi';

	/**
	 * Initialise the compatibility.
	 *
	 * @return void
	 */
	public function init() {
		// Divi's cache clearing needs a nonce in $_POST, so we can't do it via AJAX.
		// Instead, we will hook on to the asynchronous wpmdb_async_post_flush action which
		// runs using wp-cron.
		add_action( 'wpmdb_async_post_flush', [ $this, 'clear_divi_page_resources' ] );
	}

	/**
	 * Clears Divi's statically cached resources, such as page CSS files.
	 *
	 * @handles wpmdb_async_post_flush
	 *
	 * @return void
	 */
	public function clear_divi_page_resources() {
		if (
			class_exists( 'ET_Core_PageResource' ) &&
			method_exists( 'ET_Core_PageResource', 'remove_static_resources' )
		) {
			// This method call requires a nonce in $_POST unless we're running using wp_cron.
			\ET_Core_PageResource::remove_static_resources( 'all', 'all', true );
		}
	}
}
