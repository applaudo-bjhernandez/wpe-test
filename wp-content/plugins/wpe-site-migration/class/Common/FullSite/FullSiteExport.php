<?php

namespace DeliciousBrains\WPMDB\Common\FullSite;

use DeliciousBrains\WPMDB\Common\Transfers\Files\Filters\FilterInterface;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Filters\WPConfigFilter;
use DeliciousBrains\WPMDB\Common\Util\Singleton;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Data\Stage;
use WP_Error;
use ZipArchive;

/**
 * @phpstan-import-type StageName from Stage
 */
class FullSiteExport {

	const FILES_ROOT = 'files';

	const MANIFEST = 'wpmigrate-export.json';

	/**
	 * @var FilterInterface[]
	 */
	private $file_filters;

	/**
	 * @param FilterInterface[] $file_filters
	 */
	public function __construct( $file_filters = [] ) {
		$this->file_filters = $file_filters;
	}

	/**
	 * Create export file and add empty wp-content dir structure
	 *
	 * @param string $file_name
	 * @param array  $state_data
	 *
	 * @return mixed bool|WP_Error
	 * @throws WP_Error
	 **/
	public function create_export_zip( $file_name, $state_data ) {
		$zip = new ZipArchive();

		if ( $zip->open( $file_name, ZipArchive::CREATE ) !== true ) {
			return new WP_Error(
				'wp-migrate-db-export-not-created',
				__( 'Could not create ZIP Archive', 'wp-migrate-db' )
			);
		}

		$stages = json_decode( $state_data['stages'] );
		$zip->addEmptyDir( $this->determine_path( Stage::OTHER_FILES, $stages ) );
		$zip->addEmptyDir( $this->determine_path( Stage::THEME_FILES, $stages ) );
		$zip->addEmptyDir( $this->determine_path( Stage::PLUGIN_FILES, $stages ) );
		$zip->addEmptyDir( $this->determine_path( Stage::MEDIA_FILES, $stages ) );
		$zip->addFromString( self::MANIFEST, $this->get_manifest_json() );
		$zip->close();

		return true;
	}

	/**
	 * Adds batch of files to ZIP
	 *
	 * @param array $batch
	 * @param array $state_data
	 *
	 * @return mixed array|WP_Error
	 * @throws WP_Error
	 **/
	public function add_batch_to_zip( $batch, $state_data ) {
		$zip          = new ZipArchive();
		$zip_filename = $state_data['export_path'];
		$stage        = $state_data['stage'];
		$stages       = json_decode( $state_data['stages'] );
		$zip->open( $zip_filename );

		$count      = 0;
		$total_size = 0;
		$path       = $this->determine_path( $stage, $stages );

		foreach ( $batch as $key => $file ) {
			if ( file_exists( $file['absolute_path'] ) ) {
				//Apply filters to file
				$file          = $this->apply_file_filters( $file, $state_data );
				$relative_path = $stage === 'core' ? $file['relative_root_path'] : $file['relative_path'];
				$relative_path = apply_filters( 'wpmdb_export_relative_path', $relative_path, $state_data );
				$add_file      = $zip->addFile( $file['absolute_path'], $path . DIRECTORY_SEPARATOR . $relative_path );

				if ( ! $add_file ) {
					return new WP_Error(
						'wp-migrate-db-could-not-add-file-to-archive',
						sprintf( __( 'Could not add %s to ZIP Archive', 'wp-migrate-db' ), $file['name'] )
					);
				}

				$count++;
				$total_size += $file['size'];
			}
		}

		$zip->close();

		return [
			'count'      => $count,
			'total_size' => $total_size,
		];
	}

	/**
	 * Determines the file path in ZIP
	 *
	 * Returns path as defined by WP constants in wp_config.php
	 * if Core files included in export
	 *
	 * @param StageName $stage
	 * @param array     $stages
	 *
	 * @return string
	 **/
	protected function determine_path( $stage, $stages ) {
		$honor_const   = ! empty( array_intersect( [ Stage::CORE_FILES, Stage::CORE ], $stages ) );
		$honor_const   = apply_filters( 'wpmdb_export_honor_constant', $honor_const );
		$default_paths = [
			Stage::MEDIA_FILES    => 'wp-content/uploads',
			Stage::THEME_FILES    => 'wp-content/themes',
			Stage::THEMES         => 'wp-content/themes',
			Stage::PLUGIN_FILES   => 'wp-content/plugins',
			Stage::PLUGINS        => 'wp-content/plugins',
			Stage::MUPLUGIN_FILES => 'wp-content/mu-plugins',
			Stage::MUPLUGINS      => 'wp-content/mu-plugins',
			Stage::OTHER_FILES    => 'wp-content',
			Stage::OTHERS         => 'wp-content',
			Stage::CORE_FILES     => '',
			Stage::CORE           => '',
		];

		$path = $honor_const ? $this->get_relative_dir( $stage ) : $default_paths[ $stage ];

		return self::FILES_ROOT . DIRECTORY_SEPARATOR . $path;
	}

	/**
	 * Get directory relative to ABSPATH
	 *
	 * @param string $stage
	 *
	 * @return string
	 **/
	protected function get_relative_dir( $stage ) {
		return str_replace( ABSPATH, '', Util::get_stage_base_dir( $stage ) );
	}

	/**
	 * Move SQL file into ZIP archive
	 *
	 * @param string $dump_filename
	 * @param string $zip_filename
	 *
	 * @return bool
	 **/
	public function move_into_zip( $dump_filename, $zip_filename ) {
		$zip = new ZipArchive();
		$zip->open( $zip_filename );
		$add_file = $zip->addFile( $dump_filename, 'database.sql' );
		if ( $add_file ) {
			$zip->close();
			unlink( $dump_filename );

			return true;
		}

		return false;
	}

	/**
	 * Deletes ZIP archive
	 *
	 * @param string $zip_filename
	 *
	 * @return bool|WP_Error
	 **/
	public function delete_export_zip( $zip_filename ) {
		if ( false === file_exists( $zip_filename ) ) {
			return new WP_Error(
				'wp-migrate-db-could-not-find-archive-file',
				sprintf( __( ' ZIP Archive %s does not exist', 'wp-migrate-db' ), $zip_filename )
			);
		}
		$removed = unlink( $zip_filename );
		if ( false === $removed ) {
			return new WP_Error(
				'wp-migrate-db-could-not-delete-archive-file',
				sprintf( __( ' ZIP Archive %s could not be deleted', 'wp-migrate-db' ), $zip_filename )
			);
		}

		return true;
	}

	/**
	 * Creates JSON string to insert into export manifest
	 *
	 * @return string JSON
	 */
	protected function get_manifest_json() {
		$export_data = [
			'name'      => get_bloginfo( 'name' ),
			'domain'    => site_url(),
			'path'      => esc_html( Util::get_absolute_root_file_path() ),
			'wpVersion' => get_bloginfo( 'version' ),
			'services'  => Util::get_services(),
			'wpMigrate' => Util::getPluginMeta(),
		];

		return json_encode( $export_data, JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Iterates through file filters and apply them to the supplied file.
	 *
	 * @param array $file
	 * @param array $state_data
	 *
	 * @return array
	 */
	private function apply_file_filters( $file, $state_data ) {
		foreach ( $this->file_filters as $filter ) {
			if ( $filter->can_filter( $file, $state_data ) ) {
				$file = $filter->filter( $file );
			}
		}

		return $file;
	}

	/**
	 * Do the stages for the current migration denote we're potentially doing a full site export?
	 *
	 * @param array $stages
	 *
	 * @return bool
	 */
	public static function is_full_site_export( array $stages ) {
		if (
			! empty( array_intersect(
				$stages,
				[ Stage::MEDIA_FILES, Stage::THEME_FILES, Stage::PLUGIN_FILES, Stage::MUPLUGIN_FILES, Stage::OTHER_FILES, Stage::CORE_FILES ]
			) )
		) {
			return true;
		}

		return false;
	}
}
