<?php

namespace DeliciousBrains\WPMDB\Data\Vite;

class AssetList {
	/**
	 * @var array<string, FrontendAsset> An array of FrontendAsset objects indexed by assetKey.
	 */
	public $assets = [];

	/**
	 *
	 */
	public function __construct() {
		$this->assets = [];
	}

	/**
	 * Creates an AssetList from an array of FrontendAssets.
	 *
	 * @param array<string, FrontendAsset> $assets_as_array
	 *
	 * @return self
	 */
	public static function from_array( $assets_as_array ) {
		$asset_list = new self();
		$asset_list->assets = $assets_as_array;
		return $asset_list;
	}

	/**
	 * Creates an AssetList from a file containing JSON as specified by Vite.
	 *
	 * @param string $manifest_filename
	 *
	 * @return self
	 */
	public static function load_from_file( $manifest_filename ) {
		$asset_list = new self();
		$contents = file_get_contents( $manifest_filename );

		if ( empty( $contents ) ) {
			return $asset_list;
		}

		$asset_data = json_decode( $contents, true );

		if ( ! is_array( $asset_data ) ) {
			return $asset_list;
		}

		foreach ( $asset_data as $asset_key => $asset_data_as_array ) {
			$asset_list->add( FrontendAsset::fromArray( $asset_key, $asset_data_as_array ) );
		}

		return $asset_list;
	}

	/**
	 * Returns the asset with the given key.
	 *
	 * @param string $import_key
	 *
	 * @return FrontendAsset|null
	 */
	public function get( $import_key ) {
		return isset( $this->assets[ $import_key ] ) ? $this->assets[ $import_key ] : null;
	}

	/**
	 * Adds an item to the end of the list of assets.
	 *
	 * @param FrontendAsset $asset
	 * @return void
	 */
	public function add( $asset ) {
		$this->assets[$asset->asset_key] = $asset;
	}

	/**
	 * Returns true if the asset list is empty.
	 *
	 * @return bool
	 */
	public function is_empty() {
		return empty( $this->assets );
	}

	/**
	 * Adds a single items to the start of the list of assets. Preserves keys.
	 *
	 * @param FrontendAsset $asset
	 *
	 * @return void
	 */
	public function prepend_asset( $asset ) {
		$temp_asset_list = new AssetList();
		$temp_asset_list->add( $asset );
		$this->prepend_assets( $temp_asset_list );
	}

	/**
	 * Adds multiple items to the beginning of the list of assets. Preserves keys.
	 *
	 * @param AssetList $asset_list
	 *
	 * @return void
	 */
	public function prepend_assets( $asset_list ) {
		// Use + instead of array_merge so that the keys are preserved and the
		// duplicates with the same keys use the earlier version.
		$this->assets = $asset_list->assets + $this->assets;
	}

	/**
	 * Returns all assets that are (static) entry points.
	 *
	 * @return FrontendAsset[]
	 */
	public function get_entry_points() {
		return array_filter(
			$this->assets,
			function ( $asset ) {
				return $asset->is_entry;
			}
		);
	}


	/**
	 * Returns a list of assets that should be loaded in order, including both
	 * CSS and JS.
	 *
	 * Static imports for each asset should be listed before that asset.
	 *
	 * @return AssetList
	 */
	public function build_asset_list() {
		$build_assets = $this->get_css_assets();

		$entry_points = $this->get_entry_points();

		foreach ( $entry_points as $entry_point ) {
			$build_assets->prepend_asset( $entry_point );
			$imports = $entry_point->get_imports( $this );
			$build_assets->prepend_assets( $imports );
		}

		return $build_assets;
	}

	/**
	 * Gets CSS files from the asset list.
	 *
	 * @return AssetList
	 */
	public function get_css_assets() {
		$css_assets = array_filter(
			$this->assets,
			function ( $asset ) {
				return preg_match( '/\.css$/', $asset->file );
			}
		);

		return self::from_array( $css_assets );
	}

	/**
	 * Returns the asset list as an array.
	 *
	 * @return FrontendAsset[]
	 */
	public function get_assets_as_array() {
		return $this->assets;
	}
}
