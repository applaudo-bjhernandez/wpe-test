<?php

namespace DeliciousBrains\WPMDB\Common\Profile;

use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\WPMDBRestAPIServer;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\Plugin\Assets;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Sql\Table;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Common\Sanitize;
use WP_Error;
use WP_REST_Request;

class ProfileManager {
	/**
	 * @var Http
	 */
	private $http;

	/**
	 * @var Properties
	 */
	private $properties;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var MigrationStateManager
	 */
	private $state_manager;

	/**
	 * @var Util
	 */
	private $util;

	/**
	 * @var ErrorLog
	 */
	private $error_log;

	/**
	 * @var Table
	 */
	private $table;

	/**
	 * @var FormData
	 */
	private $form_data;

	/**
	 * @var Helper
	 */
	private $http_helper;

	/**
	 * @var Assets
	 */
	private $assets;

	/**
	 * @var WPMDBRestAPIServer
	 */
	private $rest_API_server;

	protected $valid_post_types;

	/**
	 * @var ProfileImporter
	 */
	private $profile_importer;

	/**
	 * @var string[]
	 */
	private $checkbox_options;

	/**
	 * @var array
	 */
	private $default_profile;

	/**
	 * ProfileManager constructor.
	 *
	 * @param Http                  $http
	 * @param Properties            $properties
	 * @param Settings              $settings
	 * @param MigrationStateManager $state_manager
	 * @param Util                  $util
	 * @param ErrorLog              $error_log
	 * @param Table                 $table
	 * @param FormData              $form_data
	 */
	public function __construct(
		Http $http,
		Helper $http_helper,
		Properties $properties,
		Settings $settings,
		MigrationStateManager $state_manager,
		Util $util,
		ErrorLog $error_log,
		Table $table,
		FormData $form_data,
		Assets $assets,
		WPMDBRestAPIServer $rest_API_server,
		ProfileImporter $profile_importer
	) {
		$this->default_profile = [
			'action'                    => 'savefile',
			'save_computer'             => '1',
			'gzip_file'                 => '1',
			'table_migrate_option'      => 'migrate_only_with_prefix',
			'replace_guids'             => '1',
			'default_profile'           => true,
			'name'                      => '',
			'select_tables'             => [],
			'select_post_types'         => [],
			'backup_option'             => 'backup_only_with_prefix',
			'exclude_transients'        => '1',
			'compatibility_older_mysql' => '0',
			'import_find_replace'       => '1',
		];

		$this->checkbox_options = [
			'save_computer'             => '0',
			'gzip_file'                 => '0',
			'replace_guids'             => '0',
			'exclude_spam'              => '0',
			'keep_active_plugins'       => '0',
			'keep_blog_public'          => '0',
			'create_backup'             => '0',
			'exclude_post_types'        => '0',
			'exclude_transients'        => '0',
			'compatibility_older_mysql' => '0',
			'import_find_replace'       => '0',
		];
		$this->http             = $http;
		$this->properties       = $properties;
		$this->settings         = $settings->get_settings();
		$this->state_manager    = $state_manager;
		$this->util             = $util;
		$this->error_log        = $error_log;
		$this->table            = $table;
		$this->form_data        = $form_data;
		$this->http_helper      = $http_helper;
		$this->assets           = $assets;
		$this->rest_API_server  = $rest_API_server;
		$this->profile_importer = $profile_importer;
	}

	public function register() {
		// internal AJAX handlers
		add_action( 'wp_ajax_wpmdb_delete_migration_profile', array( $this, 'ajax_delete_migration_profile' ) );
		add_action( 'wp_ajax_wpmdb_save_profile', array( $this, 'ajax_save_profile' ) );

		// REST endpoints
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		add_action( 'wpmdb_before_schema_update', [ $this->profile_importer, 'setProfileDefaults' ] );
	}

	public function register_rest_routes() {

		$this->rest_API_server->registerRestRoute(
			'/add-profile',
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'add_profile' ],
			]
		);

		$this->rest_API_server->registerRestRoute(
			'/update-profile',
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'update_profile' ],
				'args'     => [
					'current_profile'       => [
						'type'     => 'integer',
						'required' => true,
					],
					'updated_connection'     => [
						'type'     => 'JSON',
						'required' => true,
					],
					'updated_site_migration' => [
						'type'     => 'JSON',
						'required' => true,
					],
				],
			]
		);

		$this->rest_API_server->registerRestRoute(
			'/remove-profile',
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'remove_profile' ],
			]
		);

		$this->rest_API_server->registerRestRoute(
			'/rename-profile',
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'rename_profile' ],
			]
		);

		$this->rest_API_server->registerRestRoute(
			'/overwrite-profile',
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'overwrite_profile' ],
			]
		);

		$this->rest_API_server->registerRestRoute(
			'/load-profile',
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'load_profile' ],
			]
		);
	}

	/**
	 * Adds a profile to the saved profiles list.
	 *
	 * Handles the /add-profile REST API route.
	 *
 	 * Responds with an array of:
	 * - 'date'     => The current timestamp.
	 * - 'id'       => The numerical ID of the new profile added.
	 * - 'profiles' => An array of all saved profiles as formatted by get_saved_migration_profiles.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add_profile() {
		$state_data = $this->set_state_data(
			[
				'name'       => 'string',
				'id'         => 'int',
				'value'      => 'json',
				'guid'       => 'string',
			],
			'save'
		);

		if ( is_wp_error( $state_data ) ) {
			$this->http->end_ajax( $state_data );

			return;
		}

		$existing_profiles = get_site_option( WPMDB_SAVED_PROFILES_OPTION );

		$profiles = [];

		if ( ! empty( $existing_profiles ) ) {
			$profiles = $existing_profiles;
		}

		$date = current_time( 'timestamp' );

		// @TODO Check if Profile already exists with same names
		$new_profile = [
			'name'  => $state_data['name'],
			'value' => $state_data['value'],
			'date'  => $date,
			'guid'  => $state_data['guid'],
		];

		$profiles[] = $new_profile;

		update_site_option( WPMDB_SAVED_PROFILES_OPTION, $profiles);

		$this->http->end_ajax(
			[
				'date'     => $date,
				'id'       => max( array_keys( $profiles ) ),
				'profiles' => $this->assets->get_saved_migration_profiles()
			]
		);
	}

	/**
	 * Update the current profile.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 */
	public function update_profile( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		if ( ! is_int( $data['current_profile'] ) ) {
			$this->http->end_ajax(
				new WP_Error(
					'wpmdb-current_profile-type',
					__( 'Current profile incorrect type.', 'wp-migrate-db' )
				)
			);
		}

		$saved_profiles = Persistence::getSavedProfiles();
		if ( empty( $saved_profiles ) ) {
			$this->http->end_ajax(
				new WP_Error(
					'wpmdb-saved_profiles-not_found',
					__( 'Saved profiles not found.', 'wp-migrate-db' )
				)
			);
		}

		$current_profile_id = $data['current_profile'];
		if ( ! array_key_exists( $current_profile_id, $saved_profiles ) ) {
			$this->http->end_ajax(
				new WP_Error(
					'wpmdb-current_profile-not_found',
					__( 'Current profile not found.', 'wp-migrate-db' )
				)
			);
		}

		$profile_to_update = $saved_profiles[ $current_profile_id ];
		$profile_value     = json_decode( $profile_to_update['value'], true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $profile_value ) ) {
			$this->http->end_ajax(
				new WP_Error(
					'wpmdb-saved_profile-decode_failed',
					__( 'Failed to decode saved profile JSON.', 'wp-migrate-db' )
				)
			);
		}

		$result = [
			'urls_match'             => false,
			'connection_updated'     => false,
			'site_migration_updated' => false,
		];

		// Update connection info.
		$result = array_merge( $result, $this->compare_and_update_connection_info( $profile_value, $data ) );

		// Update site migration.
		$result = array_merge( $result, $this->compare_and_update_site_migration( $profile_value, $data ) );
		
		$profile_to_update['value'] = json_encode( $profile_value );

		if ( json_last_error() !== JSON_ERROR_NONE || $profile_to_update['value'] === false ) {
			$this->http->end_ajax(
				new WP_Error(
					'wpmdb-update_profile-encode_failed',
					__( 'Failed to encode profile JSON.', 'wp-migrate-db' )
				)
			);
		}

		$saved_profiles[ $current_profile_id ] = $profile_to_update;
		
		Persistence::storeSavedProfiles( $saved_profiles );

		$this->http->end_ajax( $result );
	}

	/**
	 * Compares and updates the connection info if necessary.
	 *
	 * @param array $profile_value The current profile data.
	 * @param array $data          The new data from the request.
	 *
	 * @return array The result of the comparison and update.
	 */
	private function compare_and_update_connection_info( &$profile_value, $data ) {
		$result = [
			'urls_match'               => false,
			'connection_updated'       => false,
			'connection_updates_count' => isset( $profile_value['connection_info']['connection_updates_count'] )
				? $profile_value['connection_info']['connection_updates_count']
				: 0,
		];
		// Ensure the necessary keys exist in saved profile.
		if (
			! isset(
				$profile_value['connection_info'],
				$profile_value['connection_info']['connection_state'],
				$profile_value['connection_info']['connection_state']['url'],
				$profile_value['connection_info']['connection_state']['key']
			)
		) {
			$this->log_profile_skip_reason( __( 'Profile connection information not valid', 'wp-migrate-db' ) );
			return $result;
		}

		// Ensure the necessary keys exist in updated connection.
		if (
			! isset(
				$data['updated_connection'],
				$data['updated_connection']['url'],
				$data['updated_connection']['key']
			)
		) {
			$this->log_profile_skip_reason( __( 'Updated connection information not valid', 'wp-migrate-db' ) );
			return $result;
		}

		// Only allow updating if the connection info has a matching URL.
		if ( $profile_value['connection_info']['connection_state']['url'] !== $data['updated_connection']['url'] ) {
			$this->log_profile_skip_reason( __( 'Connection information URLs do not match', 'wp-migrate-db' ) );
			return $result;
		}
		$result['urls_match'] = true;

		if ( $profile_value['connection_info']['connection_state']['key'] === $data['updated_connection']['key'] ) {
			return $result;
		}
		
		$profile_value['connection_info']['connection_state'] = $data['updated_connection'];

		if ( isset( $profile_value['connection_info']['connection_updates_count'] ) ) {
			$profile_value['connection_info']['connection_updates_count'] += 1;
		} else {
			$profile_value['connection_info']['connection_updates_count'] = 1;
		}

		$result['connection_updated']       = true;
		$result['connection_updates_count'] = $profile_value['connection_info']['connection_updates_count'];

		return $result;
	}

	/**
	 * Compares and updates site migration if necessary.
	 *
	 * @param array $profile_value The current profile data.
	 * @param array $data          The new data from the request.
	 *
	 * @return array The result of the comparison and update.
	 */
	private function compare_and_update_site_migration( &$profile_value, $data ) {
		$result = [ 'site_migration_updated' => false ];
		// Ensure the necessary keys exist in saved profile.
		if (
			! isset(
				$profile_value['site_migration'],
				$profile_value['site_migration']['notificationEmail'],
				$profile_value['site_migration']['migratorUserID']
			)
		) {
			$this->log_profile_skip_reason( __( 'Profile site migration information not valid', 'wp-migrate-db' ) );
			return $result;
		}

		// Ensure the email and ID exist in update.
		if (
			! isset(
				$data['updated_site_migration'],
				$data['updated_site_migration']['notificationEmail'],
				$data['updated_site_migration']['migratorUserID']
			)
		) {
			$this->log_profile_skip_reason( __( 'Updated site migration information not valid', 'wp-migrate-db' ) );
			return $result;
		}

		// Only update if the user ID has changed.
		if ( $profile_value['site_migration']['migratorUserID'] === $data['updated_site_migration']['migratorUserID'] ) {
			return $result;
		}

		$profile_value['site_migration']['migratorUserID']    = $data['updated_site_migration']['migratorUserID'];
		$profile_value['site_migration']['notificationEmail'] = $data['updated_site_migration']['notificationEmail'];
		$result['site_migration_updated']                     = true;

		return $result;
	}

	/**
	 * Adds a reason to the error log when skipping a profile update
	 *
	 * @param string $reason The reason why the updated was skipped
	 * 
	 * @return void
	 **/
	public function log_profile_skip_reason( $reason ) {
		error_log( 'WPMDB Profile validation : ' . $reason );
	}

	public function remove_profile() {
		$state_data = $this->set_state_data(
			[
				'guid' => 'text',
			],
			'remove'
		);

		if ( is_wp_error( $state_data ) ) {
			$this->error_log->log_error( $state_data->get_error_message() );
			$this->http->end_ajax( $state_data );

			return;
		}

		$saved_profiles = get_site_option( WPMDB_SAVED_PROFILES_OPTION );

		if ( empty( $saved_profiles ) || ! \is_array( $saved_profiles ) ) {
			$this->http->end_ajax( new WP_Error( 'wpmdb_error', __( 'Profile not found.', 'wp-migrate-db' ) ) );

			return;
		}

		$profile_key = 0;
		foreach ( $saved_profiles as $key => $profile ) {
			if ( $profile['guid'] === $state_data['guid'] ) {
				$profile_key = $key;
			}
		}

		unset( $saved_profiles[ $profile_key ] );
		update_site_option( WPMDB_SAVED_PROFILES_OPTION, $saved_profiles );

		$this->http->end_ajax( __( 'Profile removed', 'wp-migrate-db' ) );
	}

	public function rename_profile() {
		$state_data = $this->set_state_data(
			[
				'guid' => 'text',
				'name' => 'text',
			],
			'rename'
		);

		if ( is_wp_error( $state_data ) ) {
			$this->error_log->log_error( $state_data->get_error_message() );
			$this->http->end_ajax( $state_data );

			return;
		}

		$saved_profiles = get_site_option( WPMDB_SAVED_PROFILES_OPTION );

		if ( empty( $saved_profiles ) || ! \is_array( $saved_profiles ) ) {
			$this->http->end_ajax( new WP_Error( 'wpmdb_error', __( 'Profile not found.', 'wp-migrate-db' ) ) );

			return;
		}

		$profile_key = 0;
		foreach ( $saved_profiles as $key => $profile ) {
			if ( $profile['guid'] === $state_data['guid'] ) {
				$profile_key = $key;
			}
		}

		$saved_profiles[ $profile_key ]['name'] = $state_data['name'];

		update_site_option( WPMDB_SAVED_PROFILES_OPTION, $saved_profiles );

		$this->http->end_ajax( __( 'Profile saved', 'wp-migrate-db' ) );
	}

	public function overwrite_profile() {
		$state_data = $this->set_state_data(
			[
				'guid'     => 'text',
				'contents' => 'json',
			],
			'overwrite'
		);

		if ( is_wp_error( $state_data ) ) {
			$this->error_log->log_error( $state_data->get_error_message() );
			$this->http->end_ajax( $state_data );

			return;
		}

		$saved_profiles = get_site_option( WPMDB_SAVED_PROFILES_OPTION );

		if ( empty( $saved_profiles ) || ! \is_array( $saved_profiles ) ) {
			$this->http->end_ajax( new WP_Error( 'wpmdb_error', __( 'Profile not found.', 'wp-migrate-db' ) ) );

			return;
		}

		$profile_key = 0;
		foreach ( $saved_profiles as $key => $profile ) {
			if ( $profile['guid'] === $state_data['guid'] ) {
				$profile_key = $key;
			}
		}

		$profile_data = apply_filters( 'wpmdb_overwrite_profile', $state_data['contents'] );

		$saved_profiles[ $profile_key ]['value'] = $profile_data;

		// We should have formatted everything correctly by now.
		if ( isset( $saved_profiles[ $profile_key ]['imported'] ) ) {
			unset( $saved_profiles[ $profile_key ]['imported'] );
		}

		update_site_option( WPMDB_SAVED_PROFILES_OPTION, $saved_profiles );

		$this->http->end_ajax( __( 'Profile saved', 'wp-migrate-db' ) );
	}

	/**
	 * Loads and returns the specified profile.
	 *
	 * Handles the /load-profile REST API route.
	 *
	 * Responds with a WP Error if the specified profile ID is not
	 * valid or does not exist.
	 *
	 * Otherwise responds with an array of:
	 * - 'id'      => The numerical ID of the profile.
	 * - 'profile' => The profile data.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function load_profile() {
		$state_data = $this->set_state_data(
			[
				'id'      => 'string',
			],
			'load'
		);

		if ( is_wp_error( $state_data ) ) {
			$this->error_log->log_error( $state_data->get_error_message() );
			$this->http->end_ajax( $state_data );

			return;
		}

		$profile_id = $state_data['id'];

		$the_profile = $this->get_profile_by_id( $profile_id );

		if ( is_wp_error( $the_profile ) ) {
			$this->http->end_ajax( $the_profile );

			return;
		}

		// Ensure that any objects in the profile data value are encoded as
		// associative arrays.
		$parsed_profile       = json_decode( $the_profile['value'], true );
		$the_profile['value'] = json_encode( $parsed_profile );

		$this->http->end_ajax( [ 'id' => $profile_id, 'profile' => $the_profile ] );
	}

	/**
	 * Converts the POST data from JSON to a sanitized array and returns it.
	 *
	 * As this uses convert_json_body_to_post, it will also set the $_POST and
	 * $_REQUEST globals.
	 *
	 * Despite the name it has nothing specifically to do with migration state.
	 *
	 * Returns a WP_Error if the sanitized data is empty.
	 *
	 * @param array  $key_rules An array of $key => $type rules to use to
	 *                         sanitize the data.
	 * @param string $action   The name of the action being performed.
	 *                         Used in error messages.
	 *
	 * @return array|WP_Error
	 *
	 * @throws \Exception
	 */
	public function set_state_data( $key_rules, $action ) {
		$_POST   = $this->http_helper->convert_json_body_to_post();
		$context = $this->util->get_caller_function();

		$state_data = Sanitize::sanitize_data( $_POST, $key_rules, $context );

		if ( empty( $state_data ) ) {
			return new WP_Error(
				'profile-save-failed',
				sprintf( __( 'Failed to %s profile, state data is empty.', 'wp-migrate-db' ), $action )
			);
		}

		return $state_data;
	}

	/**
	 * Handler for deleting a migration profile.
	 *
	 * @return void
	 */
	function ajax_delete_migration_profile() {
		$this->http->check_ajax_referer( 'delete-migration-profile' );

		$key_rules = array(
			'action'     => 'key',
			'profile_id' => 'positive_int',
			'nonce'      => 'key',
		);

		$state_data = $this->state_manager->set_post_data( $key_rules );

		if ( is_wp_error( $state_data ) ) {
			$this->http->end_ajax( $state_data );

			return;
		}

		$key = absint( $state_data['profile_id'] );
		--$key;
		$return = '';

		if ( isset( $this->settings['profiles'][ $key ] ) ) {
			unset( $this->settings['profiles'][ $key ] );
			update_site_option( WPMDB_SETTINGS_OPTION, $this->settings );
		} else {
			$return = '-1';
		}

		$this->http->end_ajax( $return );
	}

	/**
	 * Handler for the ajax request to save a migration profile.
	 *
	 * @return void
	 */
	function ajax_save_profile() {
		$this->http->check_ajax_referer( 'save-profile' );

		$key_rules  = array(
			'action'  => 'key',
			'profile' => 'string',
			'nonce'   => 'key',
		);
		$state_data = $this->state_manager->set_post_data( $key_rules );

		if ( is_wp_error( $state_data ) ) {
			$this->http->end_ajax( $state_data );

			return;
		}

		// ***+=== @TODO - revisit usage of parse_migration_form_data
		$profile = $this->form_data->parse_and_save_migration_form_data( $state_data['profile'] );
		$profile = wp_parse_args( $profile, $this->checkbox_options );

		if ( isset( $profile['save_migration_profile_option'] ) && $profile['save_migration_profile_option'] == 'new' ) {
			$profile['name']              = $profile['create_new_profile'];
			$this->settings['profiles'][] = $profile;
		} else {
			$key                                        = $profile['save_migration_profile_option'];
			$name                                       = $this->settings['profiles'][ $key ]['name'];
			$this->settings['profiles'][ $key ]         = $profile;
			$this->settings['profiles'][ $key ]['name'] = $name;
		}

		update_site_option( WPMDB_SETTINGS_OPTION, $this->settings );
		end( $this->settings['profiles'] );
		$key = key( $this->settings['profiles'] );

		$this->http->end_ajax( $key );
	}

	function maybe_update_profile( $profile, $profile_id ) {
		$profile_changed = false;

		if ( isset( $profile['exclude_revisions'] ) ) {
			unset( $profile['exclude_revisions'] );
			$profile['select_post_types'] = array( 'revision' );
			$profile_changed              = true;
		}

		if ( isset( $profile['post_type_migrate_option'] ) && 'migrate_select_post_types' == $profile['post_type_migrate_option'] && 'pull' != $profile['action'] ) {
			unset( $profile['post_type_migrate_option'] );
			$profile['exclude_post_types'] = '1';
			$all_post_types                = $this->table->get_post_types();
			$profile['select_post_types']  = array_diff( $all_post_types, $profile['select_post_types'] );
			$profile_changed               = true;
		}

		if ( $profile_changed ) {
			$this->settings['profiles'][ $profile_id ] = $profile;
			update_site_option( WPMDB_SETTINGS_OPTION, $this->settings );
		}

		return $profile;
	}

	// Retrieves the specified profile, if -1, returns the default profile
	function get_profile( $profile_id ) {
		--$profile_id;

		if ( $profile_id == '-1' || ! isset( $this->settings['profiles'][ $profile_id ] ) ) {
			return $this->default_profile;
		}

		return $this->settings['profiles'][ $profile_id ];
	}

	/**
	 * @param array $migration_details
	 *
	 * @return array
	 */
	protected function filter_selected_tables( $migration_details, $key, $all_tables ) {
		$tables = $migration_details[ $key ];

		return array_filter(
			$tables,
			function ( $item ) use ( &$all_tables ) {
				return in_array( $item, $all_tables );
			}
		);
	}

	/**
	 * Fetches a specified profile from the options table.
	 *
	 * Defaults to 'saved' profiles, but still allows access to 'unsaved' profiles
	 * for possible future migration.
	 *
	 * Returns a WP_Error if the profile is not found.
	 *
	 * @param int    $profile_id The ID of the profile to fetch.
	 * @param string $option_key The string 'saved' or 'unsaved' to determine which option to fetch from.
	 * 
	 * @return bool|mixed|WP_Error
	 */
	public function get_profile_by_id( $profile_id, $option_key = 'saved' ) {
		$profile_type = $option_key === 'unsaved' ? WPMDB_RECENT_MIGRATIONS_OPTION : WPMDB_SAVED_PROFILES_OPTION;

		$saved_profiles = get_site_option( $profile_type );

		if ( empty( $saved_profiles ) || ! \is_array( $saved_profiles ) ) {
			return new WP_Error( 'wpmdb_profile_not_found', __( 'Profile not found.', 'wp-migrate-db' ) );
		}

		$profile_key = null;
		foreach ( $saved_profiles as $key => $profile ) {
			if ( $key === (int) $profile_id ) {
				$profile_key = $key;
				break;
			}
		}

		if ( ! isset( $saved_profiles[ $profile_key ] ) ) {
			return new WP_Error( 'wpmdb_profile_not_found', __( 'Profile not found.', 'wp-migrate-db' ) );
		}

		return $saved_profiles[ $profile_key ];
	}
}
