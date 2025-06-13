=== WP Engine Site Migration ===
Contributors: wpengine, ahmedgeek, philwebs, ianmjones, eriktorsner, dalewilliams, tysonreeder, kevinwhoffman
Tags: migrate, push, transfer, wordpress migration plugin, move site, database migration, site migration
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 5.6
Stable tag: 1.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Migrate any WordPress site to WP Engine or Flywheel.

== Description ==

Migrate any WordPress site to WP Engine or Flywheel. Copy all database tables and site files or customize the migration to include only what you need.

== Changelog ==

= WP Engine Site Migration 1.7.0 - 2025-06-09 =
* New: Database transfer retries now use a gradual backoff when the remote responds with network, system errors or retryable database errors
* New: File transfer retries now use a gradual backoff when the remote responds with network or system errors
* Changed: The last migration timestamp now reflects when the migration started rather than when it completed, providing a more accurate record for subsequent migrations
* Changed: The root-level .git directory is now automatically excluded by default when present
* Fixed: All theme files are now included when style sheet is not in the root dir
* Fixed: Cached REST API responses are now prevented by including a timestamp param on all GET requests
* Fixed: Profiles list now updates correctly after a profile is deleted or renamed
* Fixed: All assets have hashed filenames to prevent caching

= WP Engine Site Migration 1.6.0 - 2025-05-21 =
* New: The default setting for the themes and plugins stages is now `New and updated` so that migrations complete more quickly by avoiding moving files that already exist on the destination
* New: Migration Profiles are now available so that migrations can be saved for subsequent migrations
* New: The default media option is now `New and updated media` which will reduce migration time by only migrating files that have changed since the last migration
* Changed: Migrations to undefined errors are now avoided by using a more explicit method to set if a migration is in progress
* Changed: The Database panel is now collapsed by default when opening a profile in order to allow more options to be visible without scrolling
* Changed: The REST API notice is now removed from the UI when the connection returns during a migration
* Fixed: REST API errors on the `state` endpoint, preventing the migration from starting, are now displayed to the user
* Fixed: All prefixed tables are now selected when opening a saved profile containing the `all tables with prefix` option
* Fixed: Clicking an action row link from the plugins page no longer causes a deprecation warning
* Fixed: Notices about loading textdomains incorrectly are prevented

= WP Engine Site Migration 1.5.3 - 2025-05-05 =
* Changed: The must-use plugins excludes list has been updated for WP Engine platform related files

= WP Engine Site Migration 1.5.2 - 2025-01-22 =
* Fixed: Cancelling a migration no longer causes a PHP error on the remote site

= WP Engine Site Migration 1.5.1 - 2025-01-20 =
* Fixed: The str_contains function introduced in PHP 8 has been replaced with str_pos in order to reestablish compatibility back to PHP 5.6

= WP Engine Site Migration 1.5.0 - 2025-01-13 =
* New: Files and directories in the root of the WordPress site can now be transferred in order to avoid having to manually migrate root files outside of the plugin
* Changed: The presence of root files is now highlighted when configuring a migration in order to ensure that all critical files are included in the initial migration
* Changed: Child panels are now collapsed by default in order to allow more options to be visible without scrolling
* Changed: Exiting an errored migration now clears the previous migration in order to reflect changes made to the database and files in subsequent attempts
* Fixed: Multiple background processes are now prevented in order to improve performance and avoid displaying an inaccurate status in the migration progress panel
* Fixed: Directories are now scanned more efficiently in order to reduce the time required to scan a large site with thousands of top-level files in the uploads directory
* Fixed: Errors are now caught and handled more consistently in order to avoid displaying incorrect migration state and error messages
* Fixed: Long URLs and paths now wrap in order to prevent overflow outside of the Standard Search & Replace container

= WP Engine Site Migration 1.4.2 - 2024-12-02 =
* Changed: The directory scanner now handles large directories more efficiently in order to reduce the time required to initialize a migration
* Changed: The Wordfence Web Application Firewall (WAF) status is now included in diagnostic information in order to diagnose 403 errors with more certainty
* Fixed: Migrations blocked by Wordfence now provide troubleshooting steps in order to avoid 403 errors
* Fixed: JSON-encoded data for telemetry is now validated in order to avoid fatal errors caused by non-UTF-8 characters

= WP Engine Site Migration 1.4.1 - 2024-11-18 =
* Fixed: Resetting the secret key no longer requires a page refresh in order to see the new expiration date
* Fixed: Handling a non-string error message no longer causes a fatal error
* Fixed: Migrating from MariaDB to MySQL no longer alters field names for tables in rare cases
* Fixed: Migrating collations that are unsupported by the destination database no longer causes an error
* Fixed: Migrating files with the fallback file transfer method no longer exhausts the memory of a site with a low PHP memory limit

= WP Engine Site Migration 1.4.0 - 2024-10-30 =
* New: Error messages now display more helpful explanations in order to inform the troubleshooting process
* New: Redesigned error dialogs now include the ability to copy the error report to the clipboard in order to quickly communicate diagnostic information to support
* New: Database transfer errors are now retried in order to overcome temporary connection issues
* New: The file transfer speed is now optimized throughout the migration in order to migrate the site as quickly as possible
* New: The file transfer mechanism is now optimized throughout the migration in order to migrate the site as efficiently as possible
* Fixed: REST API errors now display details of the failed request in order to clarify why the UI did not respond to a command
* Fixed: SiteGround's `wordpress-starter` plugin is now excluded from migrations in order to prevent modifications to WP Admin
* Fixed: Divi CSS is now regenerated after a successful migration in order to avoid loading outdated cached styles
* Fixed: The migration progress panel now resizes in order to fit the contents of the panel
* Fixed: Plugin dependencies have been updated in order to maintain security and stability
* Removed: The upfront calculation of the database and file sizes has been removed in order to improve performance of the Site Migration page in WP Admin

= WP Engine Site Migration 1.3.0 - 2024-08-14 =
* Changed: Serialized strings are now only deserialized during search and replace if they contain a match
* Fixed: Migrating from MariaDB to MySQL no longer causes missing table errors when the source site is using MariaDB-specific options
* Fixed: Migrating parent and child themes in the same subdirectory no longer causes `rename()` and `copy()` warnings in logs
* Fixed: Exceptions caused by insufficient permissions now include more details about the file or directory involved
* Fixed: Undefined constants such as `DB_CHARSET` no longer cause errors when gathering diagnostics
* Fixed: Background migration healthchecks no longer fail to schedule on multisites
* Fixed: Permalinks are now flushed more reliably after a migration completes
* Fixed: Error notices are now more compatible with assistive technologies

= WP Engine Site Migration 1.2.1 - 2024-07-08 =
* Fixed: Subsequent migration attempts after a failed migration are no longer blocked by an undefined `platform` property
* Fixed: Errors during finalization are now surfaced when a `WP_Error` prevents the migration profile from being updated
* Fixed: Database tables with `NULL` values in `binary` columns, such as those generated by the Simply Static plugin, no longer cause WordPress database errors
* Fixed: Uploads directory `/wp-content/uploads/wpcf7_captcha/` is now excluded by default to avoid errors when the contents change mid-migration

= WP Engine Site Migration 1.2.0 - 2024-06-10 =
* Added: A new recursive scanner now results in faster initialization and improved performance to reduce the likelihood of "Scan manifest" errors
* Removed: The legacy recursive scanner is now removed
* Fixed: Excluding a symlinked file or directory in the stage's root no longer causes a "Temporary file not found" error during finalization

= WP Engine Site Migration 1.1.4 - 2024-05-29 =
* Added: An optional setting to preserve the destination's search engine visibility (i.e. the `blog_public` option) is now available when customizing the Database → Advanced Options subpanel
* Changed: Search engine visibility (i.e. the `blog_public` option) is now migrated by default to behave more consistently with other options
* Changed: Searching and replacing the path now includes a trailing slash (e.g. `/wordpress/`) to reduce the impact on content URLs
* Fixed: Error messages related to failed table and file transfers now provide more detail
* Fixed: Excluded Windows file paths no longer cause "Temporary file not found" errors during finalization

= WP Engine Site Migration 1.1.3 - 2024-05-16 =
* Fixed: Must-use plugin `/wp-content/wp-cache-memcached` is now excluded by default to prevent errors during finalization when migrating from another WP Engine site
* Fixed: Drop-in plugin `/wp-content/db.php` (commonly generated by W3 Total Cache) is now excluded by default to prevent fatal errors at the destination following a completed migration
* Fixed: Prefixing an exclude with `/` now ensures that only matches within that stage's root directory are excluded (e.g. in the Plugins panel, `/foo/` will only match `/wp-content/plugins/foo/`)
* Fixed: Total migration size is now accurately displayed when opening or refreshing the browser mid-migration
* Fixed: Table sizes now accurately reflect the `data_length` without including `index_length` in the calculation
* Fixed: Spellcheck is now disabled for exclude lists to prevent red underlines for "misspelled" words

= WP Engine Site Migration 1.1.2 - 2024-05-03 =
* Fixed: Default excludes now match `nginx-helper` and `loader.php` more precisely to reduce critical errors after a migration

= WP Engine Site Migration 1.1.1 - 2024-05-02 =
* Fixed: User roles and capabilities are no longer lost when the source site's table prefix (e.g. `wp_foo_`) begins with the destination site's table prefix (e.g. `wp_`), fixing a regression introduced in version 1.1.0

= WP Engine Site Migration 1.1.0 - 2024-04-29 =
* Changed: All default excludes can now be modified when customizing a migration
* Fixed: "Duplicate entry" errors are now less likely to occur when the database contains multiple `user_roles` entries with different prefixes

= WP Engine Site Migration 1.0.3 - 2024-04-16 =
* Fixed: Errors related to invalid characters in the database are now less likely to occur due to improved handling of tables with multiple Unicode character sets
* Fixed: The "Migration canceled" panel now only displays when a migration is fully canceled

= WP Engine Site Migration 1.0.2 - 2024-04-11 =
* Fixed: "Duplicate entry" errors are now less likely to occur when migrating a database table with binary primary keys, such as Wordfence’s `wffilemods` table

= WP Engine Site Migration 1.0.1 - 2024-04-11 =
* Fixed: "Duplicate entry" errors are now less likely to occur when migrating a database table with non-integer primary keys
* Fixed: Pressing the **Close** button now clears all migration-related database records from the options table

= WP Engine Site Migration 1.0.0 - 2024-04-04 =
* Added: The WP Engine Site Migration plugin is now generally available

= WP Engine Site Migration 1.0.0-rc.4 - 2024-04-03 =
* Changed: REST API endpoints that previously used `PUT` or `DELETE` now use `POST` for compatibility with more server configurations
* Changed: Debug logging is now disabled by default and can be enabled by setting the `wpmdb_enable_debug_log` filter to return `true`

= WP Engine Site Migration 1.0.0-rc.3 - 2024-03-18 =
* Changed: Recursive scanning is once again split across all files and directories within the `uploads` directory

= WP Engine Site Migration 1.0.0-rc.2 - 2024-03-14 =
* Changed: Connection information for WP Engine sites now uses the WordPress site URL instead of the wpengine.com domain
* Fixed: Connection information in the WP Engine User Portal is now able to be retrieved faster and more reliably
* Fixed: A change in background processing logic has been reverted to reduce the likelihood of stuck migrations

= WP Engine Site Migration 1.0.0-rc.1 - 2024-03-07 =
* Changed: All default excludes are now defined via PHP and passed to the UI
* Changed: The slower fallback file transfer method now only activates after two consecutive timeouts rather than two total timeouts
* Fixed: Scanning media files is now faster and more accurate when there are many files in the root of the uploads directory
* Fixed: ProPhoto theme compatibility has been improved by excluding cached CSS files that will be regenerated at the destination

= WP Engine Site Migration 1.0.0-beta.27 - 2024-03-01 =
* Fixed: "Duplicate entry" database errors are now less likely to occur as a result of long-running background processes
* Fixed: WP Engine User Portal links in email notifications now include the environment name to provide better context for support

= WP Engine Site Migration 1.0.0-beta.26 - 2024-02-28 =
* Added: Flywheel documentation and support links are now available in the sidebar
* Changed: The Destination panel now clarifies where to find connection information
* Fixed: Background process locking now prevents multiple background processes from running simultaneously
* Fixed: Healthcheck cron jobs no longer run immediately after a background process is dispatched

= WP Engine Site Migration 1.0.0-beta.25 - 2024-02-22 =
* Added: WP Engine caches are now automatically cleared after a completed migration to ensure changes are immediately visible
* Fixed: Failed loopback requests are now surfaced in an error panel before the migration begins rather than allowing the migration to start and immediately fail
* Fixed: Scan manifest file handling has been improved to reduce errors during initialization
* Fixed: Cached directory permissions on certain NFS filesystems no longer cause "Unable to overwrite destination file" errors during finalization
* Fixed: Symlinked theme and plugin directories are now transferred using the symlinked name to avoid "Temporary file not found" errors during finalization
* Fixed: .DS_Store excludes now include wildcard variations to avoid "Temporary file not found" errors during finalization
* Fixed: Directories that only contain a single .htaccess file no longer cause "Temporary file not found" errors during finalization

= WP Engine Site Migration 1.0.0-beta.24 - 2024-02-08 =
* Security: Plugin configuration data now uses JSON encoding instead of serialization to prevent PHP Object Injection
* Security: Unserializing an object during find and replace operations now passes `'allowed_classes' => false` to avoid instantiating the complete object and potentially running malicious code stored in the database
* Security: The wp-queue library now ensures that only its own classes can be unserialized via `allowed_classes`
* Security: The wp-background-processing library has been updated to version 1.3.0 for more secure handling of serialized data
* Fix: Sites with "bundle" or "runtime" in the domain name can now load plugin pages in WP Admin

= WP Engine Site Migration 1.0.0-beta.23 - 2024-01-24 =
* Changed: Scan manifest errors now provide unique error codes and debug logs to assist with troubleshooting

= WP Engine Site Migration 1.0.0-beta.22 - 2024-01-17 =
* Fix: Chunked file data is now reverified after a timeout occurs so the migration can pick up where it left off
* Fix: The WP Migrate plugin is no longer excluded from migrations, however it will remain deactivated to avoid conflicts with WP Engine Site Migration

= WP Engine Site Migration 1.0.0-beta.21 - 2024-01-10 =
* Added: An alternative migration method is now suggested when an error occurs
* Fixed: Recursive scanning performance is now improved due to ignoring excluded directories
* Fixed: Connecting to a multisite no longer causes an error when the primary domain is `wpenginepowered.com`
* Fixed: Errors while saving the remote manifest are now surfaced
* Fixed: Focus rings on the plugin settings page are now fully visible

= WP Engine Site Migration 1.0.0-beta.20 - 2023-12-22 =
* Fixed: Excluded media files defined in the UI are once again excluded from the migration

= WP Engine Site Migration 1.0.0-beta.19 - 2023-12-20 =
* Fixed: Excluded directories defined in the UI are now correctly excluded from the migration
* Fixed: Plugins that contain multiple files with header comments no longer cause migrations to fail

= WP Engine Site Migration 1.0.0-beta.18 - 2023-12-14 =
* Changed: Failed migration logs once again provide insight when troubleshooting a migration
* Fixed: Stuck migrations are now terminated after 4 minutes without progress
* Fixed: Third-party admin notices no longer appear on plugin pages
* Fixed: "Unsupported operand types" no longer cause errors when scanning a directory during initialization

= WP Engine Site Migration 1.0.0-beta.17 - 2023-12-11 =
* Removed: Changes from Beta 16 have been reverted to result in more stable migrations

= WP Engine Site Migration 1.0.0-beta.16 - 2023-12-07 =
* Changed: Failed migration logs now provide more insight when troubleshooting a migration
* Fixed: "Unsupported operand types" no longer cause errors when scanning a directory during initialization

= WP Engine Site Migration 1.0.0-beta.15 - 2023-11-29 =
* Added: PHP memory limit is now available in the diagnostic log to aid in troubleshooting memory issues
* Changed: The option to "Load Select Plugins for Migration Requests" has been removed as it was often used to unnecessarily load plugins during a migration, thereby increasing the likelihood of errors
* Fixed: PHP warnings related to 'open_basedir' restrictions no longer occur
* Fixed: The number of background polling requests that fetch data from the server has been reduced
* Fixed: Error handling of manifest files is now more robust

= WP Engine Site Migration 1.0.0-beta.14 - 2023-11-16 =
* Added: An alternative file transfer method is now attempted when encountering `cURL error 28`
* Fixed: Elementor CSS and references to these files in the database are now properly regenerated after a migration

= WP Engine Site Migration 1.0.0-beta.13 - 2023-11-14 =
* Fixed: Compatibility with Widgets for Google Reviews and other plugins which mix `utf8` and `utf8mb4` character sets has been improved
* Fixed: Temporary files (i.e. `tmpchunk` files) from previous migrations no longer cause errors in subsequent attempts
* Fixed: Failure to create temporary tables used in background processing now displays an error and immediately stops the migration
* Fixed: Telemetry now includes a more accurate representation of each migration based on current task data

= WP Engine Site Migration 1.0.0-beta.12 - 2023-11-08 =
* Changed: The User-Agent request header is now set to `wpe-site-migration/<version>` for all migration requests
* Changed: Migration status updates are now sent to WP Engine at regular intervals throughout the migration for better support and troubleshooting

= WP Engine Site Migration 1.0.0-beta.11 - 2023-10-31 =
* Added: cURL version is now available in the site diagnostics to provide more complete information for suppport
* Changed: All `.htaccess` files are now excluded as they are not supported on WP Engine or Flywheel
* Changed: Temporary directories on the source site are now located in the `uploads` directory where the plugin is more likely to have write permissions

= WP Engine Site Migration 1.0.0-beta.10 - 2023-10-25 =
* Changed: Connection information in the plugin Settings tab now uses the `wpengine.com` URL for WP Engine sites
* Fixed: Errors caused by file size differences are now less likely to occur as the size is reverified just before transferring
* Fixed: Errors that occur while initializing the migration are now more likely to be caught
* Fixed: Compatibility with WP Go Maps has been further improved through better handling of empty `POINT` fields

= WP Engine Site Migration 1.0.0-beta.9 - 2023-10-18 =
* Fixed: Compatibility with WP Go Maps has been improved through support for the MySQL `POINT` data type
* Fixed: Themes in nested directories no longer cause errors when finalizing the migration

= WP Engine Site Migration 1.0.0-beta.8 - 2023-10-16 =
* Changed: UpdraftPlus directory `/wp-content/updraft` is now excluded to avoid migrating backups that significantly increase size and duration
* Fixed: Only tables with the site's prefix are selected by default when customizing the selected tables

= WP Engine Site Migration 1.0.0-beta.7 - 2023-10-12 =
* Changed: Wordfence directory `/wp-content/wflogs` is now excluded to avoid errors caused by files changing in the middle of a migration
* Changed: Elementor directory `/wp-content/uploads/elementor/css` is now excluded so that CSS with the correct URLs is generated at the destination
* Fixed: Temporary tables generated by a migration no longer cause missing table errors
* Fixed: The calculated migration size no longer disappears after returning to a paused migration

= WP Engine Site Migration 1.0.0-beta.6 - 2023-10-04 =
* Added: External links that open in a new tab now have visual indicators
* Changed: Disabling the WP REST API now surfaces a warning
* Changed: Minimum WordPress and PHP requirements now deactivate the plugin if they are not met
* Fixed: Temporary directories starting with `wpmdb-tmp` are now automatically excluded from file transfers
* Fixed: Toggle buttons no longer disappear when panels are open
* Fixed: The WP Admin footer no longer moves up the page when customizing a migration
* Fixed: Error messages no longer render HTML, which could cause the surrounding page layout to break
* Fixed: Error messages related to the folder name of a theme now clarify which theme caused the error

= WP Engine Site Migration 1.0.0-beta.5 - 2023-09-28 =
* Added: WP Engine Site Migration is now available for download and testing from the User Portal.
