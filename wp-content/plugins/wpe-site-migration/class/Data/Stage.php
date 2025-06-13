<?php

namespace DeliciousBrains\WPMDB\Data;

/**
 * A class to define the stages of a migration.
 *
 * This class also defines a PHPStan type alias for the stages. This can be
 * imported where needed using @phpstan-import-type
 *
 * This is intended to be used as an incrementally-adopted improvement to
 * the code and forwards-compatible. In the future, this could be implemented
 * as a backed enum, for example.
 * (See https://www.php.net/manual/en/language.enumerations.backed.php)
 *
 * @phpstan-type StageName Stage::*
 */
class Stage {
	const TABLES = 'tables';
	const MIGRATE = 'migrate';
	// This is not currently used in PHP but will be needed in the future for
	// background imports.
	const UPLOAD = 'upload';
	const IMPORT = 'import';
	const BACKUP = 'backup';
	const FIND_REPLACE = 'find_replace';
	const MEDIA_FILES = 'media_files';
	const THEME_FILES = 'theme_files';
	const THEMES = 'themes';
	const PLUGIN_FILES = 'plugin_files';
	const PLUGINS = 'plugins';
	const MUPLUGIN_FILES = 'muplugin_files';
	const MUPLUGINS = 'muplugins';
	const OTHER_FILES = 'other_files';
	const OTHERS = 'others';
	const CORE_FILES = 'core_files';
	const CORE = 'core';
	const ROOT_FILES = 'root_files';
	const ROOT = 'root';
	const FINALIZE = 'finalize';

	/**
	 * Returns the lower-case, singular name of the stage for display purposes.
	 *
	 * An example use would be in an error like "Failed to migrate the table".
	 *
	 * @param StageName $stage The stage to get the singular name of.
	 *
	 * @return string
	 */
	public static function singular_name( $stage ) {
		$stage_singular = [
			self::TABLES => 'table',
			self::MIGRATE => 'migrate',
			self::IMPORT => 'import',
			self::BACKUP => 'backup',
			self::FIND_REPLACE => 'find replace',
			self::MEDIA_FILES => 'media',
			self::THEME_FILES => 'theme',
			self::THEMES => 'theme',
			self::PLUGIN_FILES => 'plugin',
			self::PLUGINS => 'plugin',
			self::MUPLUGIN_FILES => 'must-use plugin',
			self::MUPLUGINS => 'must-use plugin',
			self::OTHER_FILES => 'other',
			self::OTHERS => 'other',
			self::CORE_FILES => 'core',
			self::CORE => 'core',
			self::ROOT_FILES => 'root',
			self::ROOT => 'root',
			self::FINALIZE => 'finalize',
		];

		return isset( $stage_singular[ $stage ] ) ? $stage_singular[ $stage ] : $stage;
	}

	/**
	 * Converts a stage name to a suffix used in the temp directory name for
	 * that stage. Returns the stage name if no suffix is defined.
	 *
	 * These exist for backwards compatibility, in case anyone is using
	 * the wpmdb_transfers_temp_dir filter.
	 *
	 * @param StageName $stage
	 *
	 * @return mixed|string
	 */
	public static function get_stage_temp_dir_suffix( $stage ) {
		$stage_suffixes = [
			self::THEME_FILES    => 'themes',
			self::THEMES         => 'themes',
			self::PLUGIN_FILES   => 'plugins',
			self::PLUGINS        => 'plugins',
			self::MUPLUGIN_FILES => 'muplugins',
			self::MUPLUGINS      => 'muplugins',
			self::OTHER_FILES    => 'others',
			self::OTHERS         => 'others',
			self::ROOT_FILES     => 'root',
			self::ROOT           => 'root',
		];

		return isset( $stage_suffixes[ $stage ] ) ? $stage_suffixes[ $stage ] : $stage;
	}
}
