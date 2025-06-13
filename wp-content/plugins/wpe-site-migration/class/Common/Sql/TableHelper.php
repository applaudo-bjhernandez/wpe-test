<?php

namespace DeliciousBrains\WPMDB\Common\Sql;

use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Migration\MigrationHelper;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Data\Stage;
use DI\DependencyException;
use DI\NotFoundException;
use WP_Error;

/**
 * @phpstan-import-type StageName from Stage
 */
class TableHelper {
	/**
	 * @var FormData
	 */
	private $form_data;

	/**
	 * @var MigrationStateManager
	 */
	private $migration_state_manager;

	/**
	 * @var Http
	 */
	private $http;

	public function __construct(
		FormData $form_data,
		MigrationStateManager $migration_state_manager,
		Http $http
	) {
		$this->form_data               = $form_data;
		$this->migration_state_manager = $migration_state_manager;
		$this->http                    = $http;
	}

	/**
	 * Add backquotes to tables and db-names in
	 * SQL queries. Taken from phpMyAdmin.
	 *
	 * @param $a_name
	 *
	 * @return array|string
	 */
	function backquote( $a_name ) {
		if ( ! empty( $a_name ) && $a_name != '*' ) {
			if ( is_array( $a_name ) ) {
				$result = array();
				reset( $a_name );
				foreach ( $a_name as $key => $val ) {
					$result[ $key ] = '`' . $val . '`';
				}

				return $result;
			} else {
				return '`' . $a_name . '`';
			}
		} else {
			return $a_name;
		}
	}

	/**
	 * Better addslashes for SQL queries.
	 * Taken from phpMyAdmin.
	 *
	 * @param string $a_string
	 * @param bool   $is_like
	 *
	 * @return mixed
	 */
	function sql_addslashes( $a_string = '', $is_like = false ) {
		if ( $is_like ) {
			$a_string = str_replace( '\\', '\\\\\\\\', $a_string );
		} else {
			$a_string = str_replace( '\\', '\\\\', $a_string );
		}

		return str_replace( '\'', '\\\'', $a_string );
	}

	/**
	 * Ensures that the given create table sql string is compatible with the target database server version.
	 *
	 * @param string           $create_table
	 * @param string           $table
	 * @param string           $db_version
	 * @param string           $action
	 * @param StageName|string $stage
	 *
	 * @return string|WP_Error
	 */
	public function mysql_compat_filter( $create_table, $table, $db_version, $action, $stage ) {
		if ( empty( $db_version ) || empty( $action ) || empty( $stage ) ) {
			return $create_table;
		}

		if ( version_compare( $db_version, '5.6', '<' ) ) {
			// Convert utf8m4_unicode_520_ci collation to utf8mb4_unicode_ci if less than mysql 5.6
			$create_table = str_replace( 'utf8mb4_unicode_520_ci', 'utf8mb4_unicode_ci', $create_table );
			$create_table = str_replace( 'utf8_unicode_520_ci', 'utf8_unicode_ci', $create_table );
		} elseif ( apply_filters( 'wpmdb_convert_to_520', true ) ) {
			$create_table = str_replace( 'utf8mb4_unicode_ci', 'utf8mb4_unicode_520_ci', $create_table );
			$create_table = str_replace( 'utf8_unicode_ci', 'utf8_unicode_520_ci', $create_table );
			$create_table = str_replace( 'utf8mb4_general_ci', 'utf8mb4_unicode_520_ci', $create_table );
			$create_table = str_replace( 'utf8_general_ci', 'utf8_unicode_520_ci', $create_table );
		}

		if ( version_compare( $db_version, '5.5.3', '<' ) ) {
			// Remove index comments introduced in MySQL 5.5.3.
			// Following regex matches any PRIMARY KEY or KEY statement on a table definition that has a COMMENT statement attached.
			// The regex is then reset (\K) to return just the COMMENT, its string and any leading whitespace for replacing with nothing.
			$create_table = preg_replace( '/(?-i)KEY\s.*`.*`\).*\K\sCOMMENT\s\'.*\'/', '', $create_table );

			// Replace utf8mb4 introduced in MySQL 5.5.3 with utf8. As of WordPress 4.2 utf8mb4 is used by default on supported MySQL versions
			// but causes migrations to fail when the remote site uses MySQL < 5.5.3.
			$abort_utf8mb4 = false;
			if ( 'savefile' !== $action && Stage::BACKUP !== $stage ) {
				$abort_utf8mb4 = true;
			}
			// Escape hatch if user knows that site content is utf8 safe.
			$abort_utf8mb4 = apply_filters( 'wpmdb_abort_utf8mb4_to_utf8', $abort_utf8mb4 );

			$replace_count = 0;
			$create_table  = preg_replace(
				'/(COLLATE\s)utf8mb4/',
				'$1utf8',
				$create_table,
				-1,
				$replace_count
			); // Column collation

			if ( false === $abort_utf8mb4 || 0 === $replace_count ) {
				$create_table = preg_replace(
					'/(CHARACTER\sSET\s)utf8mb4/',
					'$1utf8',
					$create_table,
					-1,
					$replace_count
				); // Column charset
			}

			if ( false === $abort_utf8mb4 || 0 === $replace_count ) {
				$create_table = preg_replace(
					'/(COLLATE=)utf8mb4/',
					'$1utf8',
					$create_table,
					-1,
					$replace_count
				); // Table collation
			}

			if ( false === $abort_utf8mb4 || 0 === $replace_count ) {
				$create_table = preg_replace(
					'/(CHARSET\s?=\s?)utf8mb4/',
					'$1utf8',
					$create_table,
					-1,
					$replace_count
				); // Table charset
			}

			if ( true === $abort_utf8mb4 && 0 !== $replace_count ) {
				$return = sprintf(
					__(
						'The source site supports utf8mb4 data but the target does not, aborting migration to avoid possible data corruption. Please see %1$s for more information. (#148)',
						'wp-migrate-db-pro'
					),
					sprintf(
						'<a href="%s">%s</a>',
						'https://deliciousbrains.com/wp-migrate-db-pro/doc/source-site-supports-utf8mb4/?utm_campaign=error%2Bmessages&utm_source=MDB%2BPaid&utm_medium=insideplugin',
						__( 'our documentation', 'wp-migrate-db-pro' )
					)
				);

				return new WP_Error( 'wpmdb_error', $return );
			}
		}

		// Make sure table is safely using utf8mb4.
		if ( false !== strpos( $create_table, 'utf8mb4' ) ) {
			$create_table = static::update_table_to_consistently_use_utf8mb4( $create_table );
			$create_table = static::update_table_to_fix_index_field_lengths( $create_table );
		}

		// Ensure options that are incompatible between MySQL and MariaDB are taken care of.
		$create_table = static::mariadb_and_mysql_compat( $create_table, $action );

		// Cleanup storage engine statement.
		list( $source_engines, $destination_engines ) = static::get_storage_engines( $action );

		// Note: this is done after the MariaDB/MySQL compat to simplify the search.
		$create_table = static::storage_engines_compat( $create_table, $source_engines, $destination_engines );

		// Cleanup collate statement.
		list( $source_collations, $destination_collations ) = static::get_collations( $action );

		// Note: this is done after the MariaDB/MySQL compat to simplify the search.
		$create_table = static::collations_compat( $create_table, $source_collations, $destination_collations );

		return $create_table;
	}

	/**
	 * Get a correctly formatted dump name.
	 *
	 * @param string $dump_name
	 *
	 * @return string|WP_Error
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	function format_dump_name( $dump_name ) {
		$state_data = $this->migration_state_manager->set_post_data();

		if ( is_wp_error( $state_data ) ) {
			return $state_data;
		}

		$form_data           = $this->form_data->getFormData();
		$extension           = '.sql';
		$is_full_site_export = isset( $state_data['full_site_export'] ) ? $state_data['full_site_export'] : false;

		if ( empty( $form_data ) && empty( $state_data ) ) {
			return $dump_name . $extension;
		}

		if ( Stage::BACKUP === $state_data['stage'] ) {
			return $dump_name . $extension;
		}

		if ( 'import' === $state_data['intent'] ) {
			if ( isset( $state_data['import_info']['import_gzipped'] ) && true === $state_data['import_info']['import_gzipped'] ) {
				$extension .= '.gz';
			}
		} else {
			if ( Util::gzip() && $form_data['gzip_file'] && ! $is_full_site_export ) {
				$extension .= '.gz';
			}
		}

		return $dump_name . $extension;
	}

	/**
	 * Check that the given table is of the desired type,
	 * including single and multisite installs.
	 * eg: wp_posts, wp_2_posts
	 *
	 * The scope argument can take one of the following:
	 *
	 * 'table' - Match on the un-prefixed table name, this is the default.
	 * 'all' - Match on 'blog' and 'global' tables. No old tables are returned.
	 * 'blog' - Match the blog-level tables for the queried blog.
	 * 'global' - Match the global tables for the installation, matching multisite tables only if running multisite.
	 * 'ms_global' - Match the multisite global tables, regardless if current installation is multisite.
	 * 'non_ms_global' - Match the non multisite global tables, regardless if current installation is multisite.
	 * 'old' - Matches tables which are deprecated.
	 *
	 * @param string $desired_table Can be empty to match on tables from scopes other than 'table'.
	 * @param string $given_table
	 * @param string $scope         Optional type of table to match against, default is 'table'.
	 * @param string $new_prefix    Optional new prefix already added to $given_table.
	 * @param int    $blog_id       Optional Only used with 'blog' scope to test against a specific subsite's tables other than current for $wpdb.
	 * @param string $source_prefix Optional prefix from source site already added to $given_table.
	 *
	 * @return boolean
	 */
	function table_is(
		$desired_table,
		$given_table,
		$scope = 'table',
		$new_prefix = '',
		$blog_id = 0,
		$source_prefix = ''
	) {
		global $wpdb;

		$scopes = array( 'all', 'blog', 'global', 'ms_global', 'non_ms_global', 'old' );

		if ( ! in_array( $scope, $scopes ) ) {
			$scope = 'table';
		}

		if ( empty( $desired_table ) && 'table' === $scope ) {
			return false;
		}

		if ( ! empty( $new_prefix ) && 0 === stripos( $given_table, $new_prefix ) ) {
			$given_table = substr_replace( $given_table, $wpdb->base_prefix, 0, strlen( $new_prefix ) );
		}

		$match          = false;
		$prefix_escaped = $source_prefix
			? preg_quote( $source_prefix, '/' )
			: preg_quote( $wpdb->base_prefix, '/' );

		$desired_table_escaped = preg_quote( $desired_table, '/' );

		if ( 'table' === $scope ) {
			if (
				$wpdb->{$desired_table} == $given_table ||
				preg_match( '/^' . $prefix_escaped . '[0-9]+_' . $desired_table_escaped . '$/', $given_table )
			) {
				$match = true;
			}
		} else {
			if ( 'non_ms_global' === $scope ) {
				$tables = array_diff_key( $wpdb->tables( 'global', true, $blog_id ),
					$wpdb->tables( 'ms_global', true, $blog_id ) );
			} else {
				$tables = $wpdb->tables( $scope, true, $blog_id );
			}

			if ( ! empty( $desired_table ) ) {
				$tables = array_intersect_key( $tables, array( $desired_table => '' ) );
			}

			if ( ! empty( $tables ) ) {
				if ( $source_prefix ) {
					$local_prefix = preg_quote( $wpdb->base_prefix, '/' );
					$tables       = Util::change_tables_prefix( $tables, $local_prefix, $source_prefix );
				}
				foreach ( $tables as $table_name ) {
					if ( ! empty( $table_name ) && strtolower( $table_name ) === strtolower( $given_table ) ) {
						$match = true;
						break;
					}
				}
			}
		}

		return $match;
	}

	/**
	 * Get table name without temp_prefix if it has it.
	 *
	 * @param string $table
	 * @param string $temp_prefix
	 *
	 * @return string
	 */
	public static function non_temp_name( $table, $temp_prefix ) {
		if ( ! empty( $table ) && ! empty( $temp_prefix ) && 0 === strpos( $table, $temp_prefix ) ) {
			$table = substr( $table, strlen( $temp_prefix ) );
		}

		return $table;
	}

	/*
	 * Workaround database insert bug in WordPress when table has columns with both utf8mb3 and utf8mb4 charsets.
	 * @see https://core.trac.wordpress.org/ticket/59868
	 * wpdb::get_table_charset gets confused and uses utf8 if both utf8mb3 and utf8mb4 charsets are used.
	 * As utf8mb3 is effectively utf8, and utf8mb4 extends utf8, we can safely upgrade from utf8mb3 to utf8mb4.
	 *
	 * @param string $create_table
	 *
	 * @return string
	 */
	public static function update_table_to_consistently_use_utf8mb4( $create_table ) {
		if ( false !== strpos( $create_table, 'utf8mb4' ) && false !== strpos( $create_table, 'utf8mb3' ) ) {
			$create_table = str_replace( 'utf8mb3', 'utf8mb4', $create_table );
		}

		// Same goes for when server uses utf8 instead of utf8mb3, but we have to be a little more careful.
		if ( false !== strpos( $create_table, 'utf8mb4' ) && false !== strpos( $create_table, ' utf8_' ) ) {
			$create_table = str_replace( ' utf8_', ' utf8mb4_', $create_table );
		}

		if ( false !== strpos( $create_table, 'utf8mb4' ) && false !== strpos( $create_table, ' utf8 ' ) ) {
			$create_table = str_replace( ' utf8 ', ' utf8mb4 ', $create_table );
		}

		if (
			false !== strpos( $create_table, 'utf8mb4' ) &&
			false === strpos( $create_table, 'CHARSET=utf8mb4' ) &&
			false !== strpos( $create_table, 'CHARSET=utf8' )
		) {
			$create_table = str_replace( 'CHARSET=utf8', 'CHARSET=utf8mb4', $create_table );
		}

		if (
			false !== strpos( $create_table, 'utf8mb4' ) &&
			false === strpos( $create_table, 'COLLATE=utf8mb4' ) &&
			false !== strpos( $create_table, 'COLLATE=utf8' )
		) {
			$create_table = str_replace( 'COLLATE=utf8', 'COLLATE=utf8mb4', $create_table );
		}

		return $create_table;
	}

	/**
	 * Update index keys to ensure utf8mb4 fields do not blow out the allowed key length.
	 *
	 * @param string $create_table
	 *
	 * @return string
	 */
	public static function update_table_to_fix_index_field_lengths( $create_table ) {
		// Find field name and varchar lengths for fields using utf8mb4.
		preg_match_all(
			'/^.*`(?P<fields>\w+)`\svarchar\((?P<lengths>\d+)\).*utf8mb4.*$/im',
			$create_table,
			$field_matches
		);

		if ( ! empty( $field_matches['fields'] ) && ! empty( $field_matches['lengths'] ) ) {
			foreach ( $field_matches['fields'] as $idx => $field ) {
				// Find index use without length limiter of 191 or less when field has length greater than 191.
				if ( 191 < (int) $field_matches['lengths'][ $idx ] ) {
					// Grab (unique) index key names, index field specs, and the key length limits for the field we're interested in.
					preg_match_all(
						'/^.*(?|(?P<key_names>PRIMARY)\sKEY.*|KEY.*`(?P<key_names>\w+)`.*)(?P<specs>\(.*`' . $field . '`(?|\((?P<lengths>\d+)\)|).*\)).*$/im',
						$create_table,
						$key_matches
					);

					if ( ! empty( $key_matches['key_names'] ) && ! empty( $key_matches['specs'] ) && ! empty( $key_matches['lengths'] ) ) {
						foreach ( $key_matches['key_names'] as $key_idx => $key_name ) {
							$spec = false;

							// We have to update the spec, and then the index key line with that updated spec,
							// because the field may be used as the key name, so simple search could update too much.
							if ( empty( $key_matches['lengths'][ $key_idx ] ) ) {
								// Length not set, we need to add it.
								$spec = str_replace(
									'`' . $field . '`',
									'`' . $field . '`(191)',
									$key_matches['specs'][ $key_idx ]
								);
							} elseif ( 191 < (int) $key_matches['lengths'][ $key_idx ] ) {
								// Length too big, reduce it.
								$spec = str_replace(
									'`' . $field . '`(' . $key_matches['lengths'][ $key_idx ] . ')',
									'`' . $field . '`(191)',
									$key_matches['specs'][ $key_idx ]
								);
							}

							if ( false !== $spec ) {
								// We'll need to find and replace the index key line.
								$search = $key_matches[0][ $key_idx ];

								// Update spec within the index key line to use for replace.
								$replace = str_replace( $key_matches['specs'][ $key_idx ], $spec, $search );

								// Update the index key line within the create table statement.
								$create_table = str_replace( $search, $replace, $create_table );
							}
						}
					}

					unset( $key_matches );
				}
			}
		}

		return $create_table;
	}

	/**
	 * Get the source and destination storage engines for the current migration's type.
	 *
	 * @param string $intent
	 *
	 * @return array[]
	 */
	private static function get_storage_engines( $intent ) {
		$state_data = MigrationHelper::is_remote() ? Persistence::getRemoteStateData() : Persistence::getStateData();

		if ( 'pull' === $intent ) {
			$source_engines      = ! empty( $state_data['site_details']['remote']['storage_engines'] ) ? $state_data['site_details']['remote']['storage_engines'] : [];
			$destination_engines = ! empty( $state_data['site_details']['local']['storage_engines'] ) ? $state_data['site_details']['local']['storage_engines'] : [];
		} else {
			$source_engines      = ! empty( $state_data['site_details']['local']['storage_engines'] ) ? $state_data['site_details']['local']['storage_engines'] : [];
			$destination_engines = ! empty( $state_data['site_details']['remote']['storage_engines'] ) ? $state_data['site_details']['remote']['storage_engines'] : [];
		}

		return [ $source_engines, $destination_engines ];
	}

	/**
	 * Update create table statement to remove storage engine unsupported by destination.
	 *
	 * The destination database will use its default engine.
	 *
	 * @param string $create_table
	 * @param array  $source_engines
	 * @param array  $destination_engines
	 *
	 * @return string
	 */
	public static function storage_engines_compat( $create_table, $source_engines, $destination_engines ) {
		// If we can't get either of the site's engines, bail.
		if (
			empty( $create_table ) ||
			! is_string( $create_table ) ||
			empty( $source_engines ) ||
			! is_array( $source_engines ) ||
			empty( $destination_engines ) ||
			! is_array( $destination_engines )
		) {
			return $create_table;
		}

		$diff_engines = array_diff( $source_engines, $destination_engines );

		// If both site's engines are the same, bail regardless of whether SQL is correct,
		// DB will error if need be.
		if ( empty( $diff_engines ) ) {
			return $create_table;
		}

		$matches = [];
		$matched = preg_match(
			'/\s?ENGINE\s?=?\s?(\w+)/i',
			$create_table,
			$matches
		);

		// Engine isn't specified, or could not be captured, bail.
		if ( 1 !== $matched || empty( $matches[0] || empty( $matches[1] ) ) ) {
			return $create_table;
		}

		// Found an engine that isn't supported in destination, remove engine statement.
		if ( in_array( strtolower( $matches[1] ), $diff_engines ) ) {
			$create_table = preg_replace(
				'/\s?ENGINE\s?=?\s?\w+/i',
				'',
				$create_table,
				1
			);
		}

		return $create_table;
	}

	/**
	 * Get the source and destination collations for the current migration's type.
	 *
	 * @param string $intent
	 *
	 * @return array[]
	 */
	private static function get_collations( $intent ) {
		$state_data = MigrationHelper::is_remote() ? Persistence::getRemoteStateData() : Persistence::getStateData();

		if ( 'pull' === $intent ) {
			$source_collations      = ! empty( $state_data['site_details']['remote']['collations'] ) ? $state_data['site_details']['remote']['collations'] : [];
			$destination_collations = ! empty( $state_data['site_details']['local']['collations'] ) ? $state_data['site_details']['local']['collations'] : [];
		} else {
			$source_collations      = ! empty( $state_data['site_details']['local']['collations'] ) ? $state_data['site_details']['local']['collations'] : [];
			$destination_collations = ! empty( $state_data['site_details']['remote']['collations'] ) ? $state_data['site_details']['remote']['collations'] : [];
		}

		return [ $source_collations, $destination_collations ];
	}

	/**
	 * Update create table statement to switch collations unsupported by destination to their default for the charset.
	 *
	 * @param string $create_table
	 * @param array  $source_collations
	 * @param array  $destination_collations
	 *
	 * @return string
	 */
	public static function collations_compat( $create_table, $source_collations, $destination_collations ) {
		// If we can't get either of the site's collations, bail.
		if (
			empty( $create_table ) ||
			! is_string( $create_table ) ||
			empty( $source_collations ) ||
			! is_array( $source_collations ) ||
			empty( $source_collations['all'] ) ||
			! is_array( $source_collations['all'] ) ||
			empty( $source_collations['default'] ) ||
			! is_array( $source_collations['default'] ) ||
			empty( $destination_collations ) ||
			! is_array( $destination_collations ) ||
			empty( $destination_collations['all'] ) ||
			! is_array( $destination_collations['all'] ) ||
			empty( $destination_collations['default'] ) ||
			! is_array( $destination_collations['default'] )
		) {
			return $create_table;
		}

		$diff_collations = array_diff(
			array_keys( $source_collations['all'] ),
			array_keys( $destination_collations['all'] )
		);

		// If both site's collations are the same, bail regardless of whether SQL is correct,
		// DB will error if need be.
		if ( empty( $diff_collations ) ) {
			return $create_table;
		}

		$matches = [];
		$matched = preg_match_all(
			'/(\s?COLLATE\s?=?\s?)(\w+)/i',
			$create_table,
			$matches,
			PREG_OFFSET_CAPTURE
		);

		// Collation isn't specified, or could not be captured, bail.
		if ( empty( $matched ) || empty( $matches[0] || empty( $matches[1] ) || empty( $matches[2] ) ) ) {
			return $create_table;
		}

		// Whip over the full matches as we'll need their offsets,
		// but analyze the associated 2nd capture that holds the collation value.
		foreach ( $matches[0] as $idx => $match ) {
			$length         = strlen( $match[0] );
			$offset         = $match[1];
			$curr_statement = $matches[1][ $idx ][0];
			$curr_collation = strtolower( trim( $matches[2][ $idx ][0] ) );

			// Found a collation that isn't supported in destination,
			// try and switch it out to the charset's default on remote.
			if ( in_array( $curr_collation, $diff_collations ) ) {
				// Err, Houston, we have a problem!
				if ( empty( $source_collations['all'][ $curr_collation ]['charset'] ) ) {
					return $create_table;
				}

				$charset = $source_collations['all'][ $curr_collation ]['charset'];

				// Even the charset doesn't exist on the remote, bail as we don't want data corruption.
				if ( empty( $destination_collations['default'][ $charset ] ) ) {
					return $create_table;
				}

				$new_collation = $destination_collations['default'][ $charset ];

				$new_create_table = substr_replace(
					$create_table,
					$curr_statement . $new_collation,
					$offset,
					$length
				);

				// Don't recurse if no change made, otherwise have another look at altered statement.
				if ( $new_create_table === $create_table ) {
					return $create_table;
				} else {
					return self::collations_compat( $new_create_table, $source_collations, $destination_collations );
				}
			}
		}

		// No change.
		return $create_table;
	}

	/**
	 * Do the site details indicate that mariaDB is being used?
	 *
	 * @param array $site_details
	 *
	 * @return bool
	 */
	public static function site_using_mariadb( $site_details ) {
		if ( ! is_array( $site_details ) || empty( $site_details['db_service_name'] ) ) {
			return false;
		}

		return 'mariadb' === $site_details['db_service_name'];
	}

	/**
	 * Ensure options that are incompatible between MySQL and MariaDB are taken care of.
	 *
	 * @param string $create_table
	 * @param string $intent
	 *
	 * @return string
	 */
	private static function mariadb_and_mysql_compat( $create_table, $intent ) {
		$state_data = MigrationHelper::is_remote() ? Persistence::getRemoteStateData() : Persistence::getStateData();

		$local_is_mariadb  = empty( $state_data['site_details']['local'] ) ? false : static::site_using_mariadb( $state_data['site_details']['local'] );
		$remote_is_mariadb = empty( $state_data['site_details']['remote'] ) ? false : static::site_using_mariadb( $state_data['site_details']['remote'] );

		// If both sites are using the same db server, nothing to do, bail.
		if ( $local_is_mariadb === $remote_is_mariadb ) {
			return $create_table;
		}

		// Are we going from MariaDB to MySQL, or the opposite?
		if ( 'pull' === $intent && MigrationHelper::is_remote() ) {
			$local_is_mariadb = ! $local_is_mariadb;
		}

		if ( $local_is_mariadb ) {
			return static::mariadb_to_mysql_compat( $create_table );
		} else {
			// PHPCS thinks this is a call to a mysql_ PHP Extension function, but it's not.
			// phpcs:disable PHPCompatibility
			return static::mysql_to_mariadb_compat( $create_table );
		}
	}

	/**
	 * Update create table statement from MariaDB to be compatible with MySQL.
	 *
	 * @param string $create_table
	 *
	 * @return string
	 *
	 * NOTE: Every regex only relates to table options output last in the schema,
	 *       which never include identifiers surrounded in backticks,
	 *       so each pattern is preceded by a negative lookaround for a backtick
	 *       to exclude the line from matching.
	 */
	public static function mariadb_to_mysql_compat( $create_table ) {
		$changes = [
			'STORAGE ENGINE' => 'ENGINE',
			'TABLE_CHECKSUM' => 'CHECKSUM',
		];

		foreach ( $changes as $search => $replace ) {
			$create_table = preg_replace(
				'/(?!.*`)' . $search . '(\s?=?\s?\w+)/im',
				$replace . '$1',
				$create_table,
				1
			);
		}

		$removes_with_bare_params = [
			'IETF_QUOTES',
			'PAGE_CHECKSUM',
			'PAGE_COMPRESSED',
			'PAGE_COMPRESSION_LEVEL',
			'SEQUENCE',
			'TRANSACTIONAL',
		];

		foreach ( $removes_with_bare_params as $search ) {
			$create_table = preg_replace(
				'/(?!.*`)\s?' . $search . '\s?=?\s?\w+/im',
				'',
				$create_table,
				1
			);
		}

		$simple_removes = [
			'WITH SYSTEM VERSIONING',
		];

		foreach ( $simple_removes as $search ) {
			$create_table = preg_replace(
				'/ (?<!`)\s?' . $search . '/im',
				'',
				$create_table,
				1
			);
		}

		return $create_table;
	}

	/**
	 * Update create table statement from MySQL to be compatible with MariaDB.
	 *
	 * @param string $create_table
	 *
	 * @return string
	 *
	 *  NOTE: Every regex only relates to table options output last in the schema,
	 *        which never include identifiers surrounded in backticks,
	 *        so each pattern is preceded by a negative lookaround for a backtick
	 *        to exclude the line from matching.
	 */
	public static function mysql_to_mariadb_compat( $create_table ) {
		$removes_with_bare_params = [
			'AUTOEXTEND_SIZE',
		];

		foreach ( $removes_with_bare_params as $search ) {
			$create_table = preg_replace(
				'/(?!.*`)\s?' . $search . '\s?=?\s?\w+/im',
				'',
				$create_table,
				1
			);
		}

		$removes_with_string_params = [
			'COMPRESSION',
			'SECONDARY_ENGINE_ATTRIBUTE', // Must be processed before ENGINE_ATTRIBUTE.
			'ENGINE_ATTRIBUTE',
		];

		foreach ( $removes_with_string_params as $search ) {
			// Regex uses non-greedy ".*" to not go beyond 2nd single quote.
			$create_table = preg_replace(
				'/(?!.*`)\s?' . $search . '\s?=?\s?\'.*?\'/im',
				'',
				$create_table,
				1
			);
		}

		$simple_removes = [
			'START TRANSACTION',
		];

		foreach ( $simple_removes as $search ) {
			$create_table = preg_replace(
				'/ (?<!`)\s?' . $search . '/im',
				'',
				$create_table,
				1
			);
		}

		return $create_table;
	}
}
