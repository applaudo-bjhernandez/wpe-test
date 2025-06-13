<?php
namespace DeliciousBrains\WPMDB\Data\Vite;

/**
 * This class represents an entry from Vite's manifest.json file.
 *
 * See:https://vite.dev/guide/backend-integration.html
 */
class FrontendAsset {
	/**
	 * The assets in the manifest are keyed by paths. We store that here.
	 *
	 * For entry or dynamic entry chunks, the key is the relative src path from project root.
     * For non entry chunks, the key is the base name of the generated file prefixed with _.
	 *
	 * @var string
	 */
	public $asset_key;

	/**
	 * The path to the file relative to the output directory.
	 *
	 * @var string
	 */
	public $file;

	/**
	 * The name of the file without any cache key, prefixes or file extensions.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The path to the file relative to the project root.
	 *
	 * @var string
	 */
	public $src;

	/**
	 * An array of assetKeys that need to be loaded before this entry.
	 *
	 * Imports should be recursively loaded.
	 *
	 * @var string[]
	 */
	public $imports;

	/**
	 * An array of assetKeys that will be loaded as needed by the JS.
	 *
	 * @var string[]
	 */
	public $dynamic_imports;

	/**
	 * Flag to say if this asset is an origin entry point that should be loaded.
	 *
	 * @var boolean
	 */
	public $is_entry;

	/**
	 * Flag to say if this asset is a dynamic entry point that should be loaded
	 * to support another asset.
	 *
	 * @var boolean
	 */
	public $is_dynamic_entry;

	/**
	 * Create an object from an array in the format specified by Vite's manifest.json.
	 *
	 * @param string $key
	 * @param array  $assetAsArray
	 *
	 * @return self
	 */
	public static function fromArray( $key, $assetAsArray ) {
		$asset = new FrontendAsset();

		$asset->asset_key        = $key;
		$asset->file             = isset( $assetAsArray['file'] ) ? $assetAsArray['file'] : '';
		$asset->name             = isset( $assetAsArray['name'] ) ? $assetAsArray['name'] : '';
		$asset->src              = isset( $assetAsArray['src'] ) ? $assetAsArray['src'] : '';
		$asset->imports          = isset( $assetAsArray['imports'] ) ? $assetAsArray['imports'] : [];
		$asset->dynamic_imports  = isset( $assetAsArray['dynamicImports'] ) ? $assetAsArray['dynamicImports'] : [];
		$asset->is_entry         = isset( $assetAsArray['isEntry'] ) ? $assetAsArray['isEntry'] : false;
		$asset->is_dynamic_entry = isset( $assetAsArray['isDynamicEntry'] ) ? $assetAsArray['isDynamicEntry'] : false;

		return $asset;
	}

	/**
	 * Returns the asset's (non-dynamic) imports.
	 *
	 * @param AssetList $all_assets The full list of assets to pull the imports from.
	 *
	 * @return AssetList
	 */
	public function get_imports( $all_assets ) {
		$imports_to_return = new AssetList();
		// TODO: Recurse?
		foreach ( $this->imports as $import_key ) {
			$this_asset = $all_assets->get( $import_key );
			if ($this_asset) {
				$imports_to_return->add( $this_asset );
			}
		}

		return $imports_to_return;
	}

	/**
	 * Returns true if the asset is a JS file.
	 *
	 * @return bool
	 */
	public function is_js() {
		return substr($this->file, -3) === '.js';
	}

	/**
	 * Returns true if the asset is a CSS file.
	 *
	 * @return bool
	 */
	public function is_css() {
		return substr($this->file, -4) === '.css';
	}

	/**
	 * Returns the URI for the asset based on the base URL.
	 *
	 * @param string $base_url
	 *
	 * @return string
	 */
	public function get_uri( $base_url ) {
		return trailingslashit( $base_url ) . $this->file;
	}
}
