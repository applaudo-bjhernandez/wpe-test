<?php
/**
 * This file contains definitions of constants used in the plugin.
 *
 * Many of these are option names. In these cases a prefix is used that is
 * dependent on the product/plugin being used so that, say, WPE Site Migration
 * does not interfere with WP Migrate Pro.
 */

$slug = defined( 'WPMDB_CORE_SLUG' ) ? WPMDB_CORE_SLUG : 'wpmdb';

switch ( $slug ) {
	case 'wpe-site-migration':
		$prefix = 'wpesm_';
		break;
	default:
		$prefix = 'wpmdb_';
		break;
}

if ( ! defined( 'WPMDB_OPTION_PREFIX' ) ) {
	define( 'WPMDB_OPTION_PREFIX', $prefix );
}

/**
 * The name of the option used to store the error log used in the diagnostics
 * and customer support.
 */
if ( ! defined( 'WPMDB_ERROR_LOG_OPTION' ) ) {
    define( 'WPMDB_ERROR_LOG_OPTION', $prefix . 'error_log' );
}

/**
 * The name of the option used to store the plugin settings.
 */
if ( ! defined( 'WPMDB_SETTINGS_OPTION' ) ) {
    define( 'WPMDB_SETTINGS_OPTION', $prefix . 'settings' );
}

/**
 * The name of the option used to store the version of our profile data schema.
 */
if ( ! defined( 'WPMDB_SCHEMA_VERSION_OPTION' ) ) {
    define( 'WPMDB_SCHEMA_VERSION_OPTION', $prefix . 'schema_version' );
}

/**
 * The name of the option used to store saved profiles.
 */
if ( ! defined( 'WPMDB_SAVED_PROFILES_OPTION' ) ) {
    define( 'WPMDB_SAVED_PROFILES_OPTION', $prefix . 'saved_profiles' );
}

/**
 * The name of the option used to store recent migration profiles.
 */
if ( ! defined( 'WPMDB_RECENT_MIGRATIONS_OPTION' ) ) {
    define( 'WPMDB_RECENT_MIGRATIONS_OPTION', $prefix . 'recent_migrations' );
}

/**
 * The name of the option used to store the state of the other site in the
 * migration, if that state is known.
 */
if ( ! defined( 'WPMDB_REMOTE_MIGRATION_STATE_OPTION' ) ) {
    define( 'WPMDB_REMOTE_MIGRATION_STATE_OPTION', $prefix . 'remote_migration_state' );
}

/**
 * The name of the option used to store the current migration state.
 */
if ( ! defined( 'WPMDB_MIGRATION_STATE_OPTION' ) ) {
    define( 'WPMDB_MIGRATION_STATE_OPTION', $prefix . 'migration_state' );
}

/**
 * The name of the option used to store the current migration options.
 */
if ( ! defined( 'WPMDB_MIGRATION_OPTIONS_OPTION' ) ) {
    define( 'WPMDB_MIGRATION_OPTIONS_OPTION', $prefix . 'migration_options' );
}

/**
 * The name of the option used to store the remote site's response to the
 * connection request, complete with all the remote site's details.
 */
if ( ! defined( 'WPMDB_REMOTE_RESPONSE_OPTION' ) ) {
    define( 'WPMDB_REMOTE_RESPONSE_OPTION', $prefix . 'remote_response' );
}

/**
 * The name of the option used to store the "usage". This is a record of the
 * most recent 'qualified' plugin use, and it stores the usage timestamp as
 * well as the action (push/pull/export/find-replace). This is sent in
 * requests to the Delicious Brains licensing/usage API.
 */
if ( ! defined( 'WPMDB_USAGE_OPTION' ) ) {
    define( 'WPMDB_USAGE_OPTION', $prefix . 'usage' );
}

/**
 * The name of the transient used to store the queue status.
 */
if ( ! defined( 'WPMDB_QUEUE_STATUS_OPTION' ) ) {
    define( 'WPMDB_QUEUE_STATUS_OPTION', $prefix . 'queue_status' );
}

/**
 * Folder transfer media files option.
 *
 * TODO: This does not appear to be used, except that the option is deleted. Can it be removed?
 */
if ( ! defined( 'WPMDB_FOLDER_TRANSFER_MEDIA_FILES_OPTION' ) ) {
    define( 'WPMDB_FOLDER_TRANSFER_MEDIA_FILES_OPTION', $prefix . 'folder_transfers_media_files_' );
}

/**
 * Folder transfer theme files option.
 *
 * TODO: This does not appear to be used, except that the option is deleted. Can it be removed?
 */
if ( ! defined( 'WPMDB_FOLDER_TRANSFER_THEME_FILES_OPTION' ) ) {
    define( 'WPMDB_FOLDER_TRANSFER_THEME_FILES_OPTION', $prefix . 'folder_transfers_themes_' );
}

/**
 * Folder transfer plugin files option.
 *
 * TODO: This does not appear to be used, except that the option is deleted. Can it be removed?
 */
if ( ! defined( 'WPMDB_FOLDER_TRANSFER_PLUGIN_FILES_OPTION' ) ) {
    define( 'WPMDB_FOLDER_TRANSFER_PLUGIN_FILES_OPTION', $prefix . 'folder_transfers_plugins_' );
}

/**
 * Folder transfer muplugin files option.
 *
 * TODO: This does not appear to be used, except that the option is deleted. Can it be removed?
 */
if ( ! defined( 'WPMDB_FOLDER_TRANSFER_MUPLUGIN_FILES_OPTION' ) ) {
    define( 'WPMDB_FOLDER_TRANSFER_MUPLUGIN_FILES_OPTION', $prefix . 'folder_transfers_muplugins_' );
}

/**
 * Folder transfer other files option.
 *
 * TODO: This does not appear to be used, except that the option is deleted. Can it be removed?
 */
if ( ! defined( 'WPMDB_FOLDER_TRANSFER_OTHER_FILES_OPTION' ) ) {
    define( 'WPMDB_FOLDER_TRANSFER_OTHER_FILES_OPTION', $prefix . 'folder_transfers_others_' );
}

/**
 * Folder transfer core files option.
 *
 * TODO: This does not appear to be used, except that the option is deleted. Can it be removed?
 */
if ( ! defined( 'WPMDB_FOLDER_TRANSFER_CORE_FILES_OPTION' ) ) {
    define( 'WPMDB_FOLDER_TRANSFER_CORE_FILES_OPTION', $prefix . 'folder_transfers_core_' );
}

/**
 * The name of the transient used to store update data from the Delicious
 * Brains licensing API.
 */
if ( ! defined( 'WPMDB_UPDATE_DATA_OPTION' ) ) {
    define( 'WPMDB_UPDATE_DATA_OPTION', $prefix . 'upgrade_data' );
}

/**
 * The name of the transient used to store an HTML message when the
 * Delicious Brains licensing/update API is down.
 */
if ( ! defined( 'WPMDB_DBRAINS_API_DOWN_OPTION' ) ) {
    define( 'WPMDB_DBRAINS_API_DOWN_OPTION', $prefix . 'dbrains_api_down' );
}

/**
 * The name of the transient set when legacy addons have been disabled.
 */
if ( ! defined( 'WPMDB_DISABLED_LEGACY_ADDONS_OPTION' ) ) {
    define( 'WPMDB_DISABLED_LEGACY_ADDONS_OPTION', $prefix . 'disabled_legacy_addons' );
}

/**
 * The name of the transient used to store the option to temporarily disable SSL.
 */
if ( ! defined( 'WPMDB_TEMPORARILY_DISABLE_SSL_OPTION' ) ) {
    define( 'WPMDB_TEMPORARILY_DISABLE_SSL_OPTION', $prefix . 'temporarily_disable_ssl' );
}

/**
 * The name of the transient used to store the help message when a licensing
 * API request contained such a message.
 */
if ( ! defined( 'WPMDB_HELP_MESSAGE_OPTION' ) ) {
    define( 'WPMDB_HELP_MESSAGE_OPTION', $prefix . 'help_message' );
}

/**
 * The name of the transient used to store the list of addons returned by the
 * licensing API.
 */
if ( ! defined( 'WPMDB_ADDONS_OPTION' ) ) {
    define( 'WPMDB_ADDONS_OPTION', $prefix . 'addons' );
}

/**
 * The name of the option that stores the version number of the addon schema.
 */
if ( ! defined( 'WPMDB_ADDON_SCHEMA_OPTION' ) ) {
    define( 'WPMDB_ADDON_SCHEMA_OPTION', $prefix . 'addon_schema' );
}

/**
 * The name of the option used for checking plugin version requirements for addons.
 */
if ( ! defined( 'WPMDB_ADDON_REQUIREMENT_CHECK_OPTION' ) ) {
    define( 'WPMDB_ADDON_REQUIREMENT_CHECK_OPTION', $prefix . 'addon_requirement_check' );
}

/**
 * The name of the option used to store the local site's basic auth credentials.
 */
if ( ! defined( 'WPMDB_SITE_BASIC_AUTH_OPTION' ) ) {
    define( 'WPMDB_SITE_BASIC_AUTH_OPTION', $prefix . 'site_basic_auth' );
}

/**
 * The name of the transient used to store the current Migration ID.
 *
 * Note from RW who added these comments: This seems to only be used by
 * MigrationState and MigrationStateManager, but these do not seem to be used
 * any more, not even in v2.x!
 */
if ( ! defined( 'WPMDB_MIGRATION_ID_TRANSIENT' ) ) {
    define( 'WPMDB_MIGRATION_ID_TRANSIENT', $prefix . 'migration_id' );
}

/**
 * The name of the option used to store the WPE auth cookie that is needed
 * to be able to write files when migrating to WP Engine.
 *
 * For history see: https://github.com/deliciousbrains/wp-migrate-db-pro/issues/3651
 */
if ( ! defined( 'WPMDB_WPE_REMOTE_COOKIE_OPTION' ) ) {
    define( 'WPMDB_WPE_REMOTE_COOKIE_OPTION', $prefix . 'wpe_remote_cookie' );
}

/**
 * The prefix of the option used to store file chunk data.
 */
if ( ! defined( 'WPMDB_FILE_CHUNK_OPTION_PREFIX' ) ) {
    define( 'WPMDB_FILE_CHUNK_OPTION_PREFIX', $prefix . 'file_chunk_' );
}

/**
 * The name of the transient used to cache the response from the license
 * server. This transient name will have a user ID added as responses are
 * user specific.
 */
if ( ! defined( 'WPMDB_LICENSE_RESPONSE_TRANSIENT' ) ) {
    define( 'WPMDB_LICENSE_RESPONSE_TRANSIENT', $prefix . 'licence_response' );
}

/**
 * The name of the transient used to cache the available addons reported by
 * the license server. If a user-id specific version of this is needed then
 * the WPMDB_AVAILABLE_ADDONS_PER_USER_TRANSIENT constant should be used as
 * a prefix.
 */
if ( ! defined( 'WPMDB_AVAILABLE_ADDONS_TRANSIENT' ) ) {
    define( 'WPMDB_AVAILABLE_ADDONS_TRANSIENT', $prefix . 'available_addons' );
}

/**
 * The name of the transient used to cache the available addons for a
 * specific user ID. This transient name will have a user ID added.
 */
if ( ! defined( 'WPMDB_AVAILABLE_ADDONS_PER_USER_TRANSIENT' ) ) {
    define( 'WPMDB_AVAILABLE_ADDONS_PER_USER_TRANSIENT', $prefix . 'available_addons_per_user_' );
}

/**
 * The name of the transient used to store the response from the WP Engine
 * product info API. This is only used in WPESM.
 */
if ( ! defined( 'WPMDB_PRODUCT_INFO_RESPONSE_TRANSIENT' ) ) {
    define( 'WPMDB_PRODUCT_INFO_RESPONSE_TRANSIENT', $prefix . 'product_info_response' );
}

/**
 * The name of the key used to store a user's license key in user meta.
 */
if ( ! defined( 'WPMDB_LICENSE_KEY_USER_META' ) ) {
    define( 'WPMDB_LICENSE_KEY_USER_META', $prefix . 'licence_key' );
}

/**
 * The name of the option used to store migration stats.
 */
if ( ! defined( 'WPMDB_MIGRATION_STATS_OPTION' ) ) {
    define( 'WPMDB_MIGRATION_STATS_OPTION', $prefix . 'migration_stats' );
}

/**
 * The name of the transient used to store the ID of a notice that should be
 * displayed when we deactivate another of our migration plugins to prevent
 * a conflict.
 *
 * This can not be overridden as it is for internal use only. The possible
 * notice ID values follow.
 */
define( 'WPMDB_DEACTIVATED_NOTICE_ID_TRANSIENT', 'wpmdb_deactivated_notice_id' );
define( 'WPMDB_LITE_DEACTIVATED_FOR_PRO_ID', '1' );
define( 'WPMDB_PRO_DEACTIVATED_FOR_LITE_ID', '2' );
define( 'WPMDB_DEACTIVATED_FOR_WPESM_ID', '3' );
define( 'WPESM_DEACTIVATED_FOR_WPMDB_ID', '4' );
