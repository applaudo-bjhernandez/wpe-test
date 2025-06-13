<?php
/**
 * Entrypoint for the theme.
 */

namespace ReactWPScripts;

use DeliciousBrains\WPMDB\Data\Vite\AssetList;
use DeliciousBrains\WPMDB\Data\Vite\FrontendAsset;
use DeliciousBrains\WPMDB\Services\Vite;

/**
 * Is this a development environment?
 *
 * @return bool
 */
function is_development() {
	if ( defined( 'WPMDB_REACT_SCRIPTS_IS_DEV' ) ) {
		return WPMDB_REACT_SCRIPTS_IS_DEV;
	}

	$env = isset( $_ENV['MDB_IS_DEV'] ) ? (bool) $_ENV['MDB_IS_DEV'] : false;

	// @codingStandardsIgnoreLine
	return apply_filters( 'reactwpscripts.is_development', $env );
}

function switch_slashes_for_windows( $path ) {
	return str_replace( '\\', '/', $path );
}

function get_build_folder_name() {
	if ( defined( 'WPMDB_PRO' ) && WPMDB_PRO ) {
		return 'build';
	}

	if ( defined( 'WPE_MIGRATIONS' ) && WPE_MIGRATIONS ) {
		return 'build-wpe';
	}

	return 'build-free';
}

/**
 * Loads the asset manifest file, and returns a list of FrontendAssets.
 *
 * @param string $directory Root directory containing `src` and `build` directory.
 *
 * @return AssetList
 */
function get_assets_list( $directory, $base_url ) {
	$directory    = trailingslashit( $directory );
	$build_folder = get_build_folder_name();

	$production_assets = AssetList::load_from_file( $directory . $build_folder . '/asset-manifest.json' );

	return $production_assets->build_asset_list();
}

/**
 * Infer a base web URL for a file system path.
 *
 * @param string $path Filesystem path for which to return a URL.
 *
 * @return string|null
 */
function infer_base_url( $path ) {
	$path = wp_normalize_path( $path );

	$stylesheet_directory = wp_normalize_path( get_stylesheet_directory() );
	if ( strpos( $path, $stylesheet_directory ) === 0 ) {
		return get_theme_file_uri( substr( $path, strlen( $stylesheet_directory ) ) );
	}

	$template_directory = wp_normalize_path( get_template_directory() );
	if ( strpos( $path, $template_directory ) === 0 ) {
		return get_theme_file_uri( substr( $path, strlen( $template_directory ) ) );
	}

	// Any path not known to exist within a theme is treated as a plugin path.
	$plugin_path = get_plugin_basedir_path();
	if ( strpos( $path, $plugin_path ) === 0 ) {
		return plugin_dir_url( __FILE__ ) . substr( $path, strlen( $plugin_path ) + 1 );
	}

	return '';
}

/**
 * Return the path of the plugin basedir.
 *
 * @return string
 */
function get_plugin_basedir_path() {
	$plugin_dir_path = wp_normalize_path( plugin_dir_path( __FILE__ ) );

	$plugins_dir_path = wp_normalize_path( trailingslashit( WP_PLUGIN_DIR ) );

	return substr( $plugin_dir_path, 0, strpos( $plugin_dir_path, '/', strlen( $plugins_dir_path ) + 1 ) );
}

/**
 * Return web URIs or convert relative filesystem paths to absolute paths.
 *
 * @param string $asset_path A relative filesystem path or full resource URI.
 * @param string $base_url   A base URL to prepend to relative bundle URIs.
 *
 * @return string
 */
function get_asset_uri( $asset_path, $base_url ) {
	// If it has a URL scheme, or is a relative URL as defined via WP_CONTENT_DIR or similar.
	if (
		strpos( $asset_path, '://' ) !== false ||
		plugins_url() === substr( $asset_path, 0, strlen( plugins_url() ) )
	) {
		return $asset_path;
	}

	return trailingslashit( $base_url ) . $asset_path;
}

/**
 * @param string $directory Root directory containing `src` and `build` directory.
 * @param array  $opts      {
 *
 * @type string  $base_url  Root URL containing `src` and `build` directory. Only needed for production.
 * @type string  $handle    Style/script handle. (Default is last part of directory name.)
 * @type array   $scripts   Script dependencies.
 * @type array   $styles    Style dependencies.
 */
function enqueue_assets( $directory, $opts = [] ) {
	$defaults = [
		'base_url' => '',
		'handle'   => basename( $directory ),
		'scripts'  => [
			'wp-date',
		],
		'styles'   => [],
	];

	$opts = wp_parse_args( $opts, $defaults );

	$base_url = $opts['base_url'];
	if ( empty( $base_url ) ) {
		$base_url = infer_base_url( $directory );
	}

	$build_url = trailingslashit( $base_url ) . get_build_folder_name();

	if ( is_development() ) {
		// We ONLY enqueue the Vite dev scripts in development.
		// Note that the Vite dev server includes the CSS as the Router imports the styles.
		Vite::enqueue_vite_dev_build_scripts( $base_url );
		return;
	}

	$assets = get_assets_list( $directory, $base_url );

	if ( $assets->is_empty() ) {
		if ( WP_DEBUG ) {
			handle_assets_error();
		}

		// @codingStandardsIgnoreLine
		trigger_error( 'React WP Scripts Error: Unable to find React asset manifest', E_USER_WARNING );

		return;
	}

	foreach ( $assets->get_assets_as_array() as $asset ) {
		// Set a dynamic handle as we can have more than one JS entry point.
		// Treats the runtime file as primary to make setting dependencies easier.
		$handle = $opts['handle'] . '-' . sanitize_key( basename( $asset->file ) );

		if ( $asset->is_js() && $asset->is_entry && ! is_development() ) {
			// There will only be one entry point, and it will a JS file that exports the wpmigrate function.
			add_action( 'admin_print_footer_scripts', function () use ( $asset, $build_url ) {
				echo "<script type=\"module\">";
				echo "  import wpmigrate from '{$asset->get_uri( $build_url )}';";
				echo "  wpmigrate();";
				echo "</script>";
			} );
		} elseif ( $asset->is_css() ) {
			wp_enqueue_style(
				$handle,
				$asset->get_uri( $build_url ),
				$opts['styles'],
				false // @codingStandardsIgnoreLine
			);
		}
	}
}

/**
 * Display an overlay error when the React bundle cannot be loaded. It also stops the execution.
 *
 * @param array $details
 */
function handle_assets_error( $details = [] ) {
	?>
	<style>
			/**
			 * Copyright (c) 2015-present, Facebook, Inc.
			 *
			 * This source code is licensed under the MIT license found in the
			 * LICENSE file in the root directory of this source tree.
			 */

			/* @flow */

			html, body {
				overflow: hidden;
			}

			.error-overlay {
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				border: none;
				z-index: 1000;

				--black: #293238;
				--dark-gray: #878e91;
				--red: #ce1126;
				--red-transparent: rgba(206, 17, 38, 0.05);
				--light-red: #fccfcf;
				--yellow: #fbf5b4;
				--yellow-transparent: rgba(251, 245, 180, 0.3);
				--white: #ffffff;
			}

			.error-overlay .wrapper {
				width: 100%;
				height: 100%;
				box-sizing: border-box;
				text-align: center;
				background-color: var(--white);
			}

			.primaryErrorStyle {
				background-color: var(--red-transparent);
			}

			.error-overlay .overlay {
				position: relative;
				display: inline-flex;
				flex-direction: column;
				height: 100%;
				width: 1024px;
				max-width: 100%;
				overflow-x: hidden;
				overflow-y: auto;
				padding: 0.5rem;
				box-sizing: border-box;
				text-align: left;
				font-family: Consolas, Menlo, monospace;
				font-size: 13px;
				line-height: 2;
				color: var(--black);
			}

			.header {
				font-size: 2em;
				font-family: sans-serif;
				color: var(--red);
				white-space: pre-wrap;
				margin: 0 2rem 0.75rem 0;
				flex: 0 0 auto;
				max-height: 50%;
				overflow: auto;
			}

			.error-content {
				padding: 1rem;
			}

			code {
				background-color: rgba(27, 31, 35, .05);
				margin: 0;
				padding: .2em .4em;
			}
	</style>
	<div class="error-overlay">
		<div class="wrapper primaryErrorStyle">
			<div class="overlay">
				<div class="header">Failed to render</div>
				<div class="error-content primaryErrorStyle">
					Unable to find React asset manifest.
					<code>react-wp-scripts</code> was unable to find either a development or production asset manifest.
					Run <code>npm start</code> to start the development server or
					<code>npm run build</code> to build a production bundle.
				</div>
			</div>
		</div>
	</div>
	<?php

	die();
}
