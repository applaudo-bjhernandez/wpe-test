<?php
namespace DeliciousBrains\WPMDB\Services;

use DeliciousBrains\WPMDB\Common\Util\Util;

/**
 * Service class for functions related to the Vite build process.
 */
class Vite {

	/**
	 * This array maps the environment type to the JS build entry point file.
	 *
	 * For environment type strings see \DeliciousBrains\WPMDB\Common\Util\Util::appEnv
	 *
	 * @var array<string,string>
	 */
	const ENV_TO_ENTRY_FILE_MAP = [
		'pro' => 'indexPro.jsx',
		'wpe' => 'indexWPE.jsx',
		'free' => 'indexFree.jsx'
	];

	/**
	 * Enqueues the Vite build scripts for the development environment.
	 *
	 * This needs to use a closure to access the $base_url variable.
	 *
	 * @param string $base_url The base URL for the Vite build.
	 * @return void
	 */
	public static function enqueue_vite_dev_build_scripts( $base_url ) {
		add_action(
			'admin_print_footer_scripts',
			function () use ( $base_url ) {
				self::print_vite_dev_scripts( $base_url );
			}
		);
	}

	/**
	 * Prints the Vite build scripts for the development environment.
	 *
	 * @param string $base_url The base URL for the Vite build.
	 * @return void
	 *
	 * @handles admin_print_footer_scripts
	 */
	public static function print_vite_dev_scripts( $base_url ) {
		$entry_file = self::ENV_TO_ENTRY_FILE_MAP[ Util::appEnv() ];

		// Ignore the phpcs warnings for the non-enqueued script.
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		// phpcs:disable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
		echo <<< EOT
<!-- This is needed for Vite + React. See: https://vite.dev/guide/backend-integration.html -->
<script type="module">
  import RefreshRuntime from 'http://localhost:5173/@react-refresh'
  RefreshRuntime.injectIntoGlobalHook(window)
  window.\$RefreshReg\$ = () => {}
  window.\$RefreshSig\$ = () => (type) => type
  window.__vite_plugin_react_preamble_installed__ = true
</script>
<script type="module" src="http://localhost:5173/@vite/client"></script>
<!-- Note that this path is relative to the Vite root -->
<script type="module" src="http://localhost:5173/src/{$entry_file}"></script>
<script type="module">
	// In development, Vite outputs assets paths relative to the entry point.
	// This variable is used in the JS to prepend the base dir to the relative
	// asset URL. See the JS `getAsset` function in `Utils.jsx`
	window.vitePluginBuildURL = '{$base_url}';
  
	import wpmigrate from 'http://localhost:5173/src/{$entry_file}'
	wpmigrate();
</script>
EOT;
		// phpcs:enable
	}
}
