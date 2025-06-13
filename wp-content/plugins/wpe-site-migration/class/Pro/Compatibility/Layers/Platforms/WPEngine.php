<?php

namespace DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Platforms;

use DeliciousBrains\WPMDB\Common\Util\Util;

class WPEngine extends AbstractPlatform {
	/**
	 * @var string
	 */
	protected static $key = 'wp_engine';

	/**
	 * Primary domain given to customers.
	 *
	 * @var string
	 */
	protected static $primary_domain = 'wpengine.com';

	/**
	 * Alternate domain given to customers.
	 *
	 * @var string
	 */
	protected static $alternate_domain = 'wpenginepowered.com';

	public function __construct() {
		parent::__construct();

		add_filter( 'wpmdb_get_alternate_connection_url', [ $this, 'filter_get_alternate_connection_url' ], 10, 2 );
		add_action( 'wpmdb_flush', [ $this, 'purge_all_cache' ] );
	}

	/**
	 * Are we running on this platform?
	 *
	 * @return bool
	 */
	public static function is_platform() {
		$wpe_cookie = Util::get_wpe_cookie();

		return ! empty( $wpe_cookie );
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
	 * Filter request to see whether there is an alternate connection URL that can be tried.
	 *
	 * @param bool|string $alt_url
	 * @param string      $ajax_url
	 *
	 * @return bool|string
	 */
	public function filter_get_alternate_connection_url( $alt_url, $ajax_url ) {
		if ( ! empty( $alt_url ) || empty( $ajax_url ) || ! is_string( $ajax_url ) ) {
			return $alt_url;
		}

		$host = wp_parse_url( $ajax_url, PHP_URL_HOST );

		if ( empty( $host ) ) {
			return $alt_url;
		}

		if ( substr( $host, -strlen( self::$primary_domain ) ) === self::$primary_domain ) {
			$count   = 0;
			$new_url = str_replace( self::$primary_domain, self::$alternate_domain, $ajax_url, $count );

			if ( 1 === $count ) {
				return $new_url;
			}
		}

		return $alt_url;
	}

	/**
	 * Purges all cache on WP Engine.
	 *
	 * @handles wpmdb_flush
	 *
	 * @return void
	 */
	public function purge_all_cache() {
		if (
			method_exists( 'WpeCommon', 'purge_memcached' ) &&
			method_exists( 'WpeCommon', 'clear_maxcdn_cache' ) &&
			method_exists( 'WpeCommon', 'purge_varnish_cache' )
		) {
			\WpeCommon::purge_memcached();
			\WpeCommon::clear_maxcdn_cache();
			\WpeCommon::purge_varnish_cache();
		}
	}
}
