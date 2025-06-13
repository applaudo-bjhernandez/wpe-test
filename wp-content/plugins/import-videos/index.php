<?php
/**
* Plugin Name: Import Videos
* Description: Import videos from API.
* Version: 0.1
* Author: Applaudo Studios
**/

if (!defined('ABSPATH')) {
    exit;
}

require 'vendor/autoload.php';

if (defined( 'WP_CLI' ) && WP_CLI) {
    WP_CLI::add_command( 'zype', \TnL\ImportVideos\ZypeImportCommand::class );
}


// Create or update table for logs

register_activation_hook(__FILE__, 'create_import_log_table');
function create_import_log_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'zype_import_logs';
    
    $charset_collate = $wpdb->get_charset_collate();
    

    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // If the table does not exist, create it
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            imported_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            post_type varchar(255) NOT NULL,
            imported_videos int NOT NULL,
            status varchar(255) NOT NULL,  // Added the 'status' field
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    } else {
        // If the table exists, check if the 'status' column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'status'");
        if (empty($column_exists)) {
            // If the 'status' column does not exist, add it
            $wpdb->query("ALTER TABLE $table_name ADD status varchar(255) NOT NULL");
        }
    }
}

// Agregar página al menú del dashboard
add_action('admin_menu', 'zype_import_log_menu');
function zype_import_log_menu() {
    add_menu_page(
        'Zype Import Logs', // Page title
        'Zype Import Logs', // Munu title
        'manage_options',   // Capability
        'zype-import-logs', // Slug
        'display_import_logs' // Funtion to show info
    );
}

// Show in dashboard
function display_import_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'zype_import_logs';
    
    // Get data from database
    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY imported_at DESC");
    
    // Show result in table
    echo '<div class="wrap"><h1>Zype Import Logs</h1>';
    echo '<table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Post Type</th>
                    <th>Videos Imported</th>
                    <th>Status</th> <!-- Add Status column header -->
                </tr>
            </thead>
            <tbody>';
    
    if ($logs) {
        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>' . esc_html($log->imported_at) . '</td>';
            echo '<td>' . esc_html($log->post_type) . '</td>';
            echo '<td>' . esc_html($log->imported_videos) . '</td>';
            echo '<td>' . esc_html($log->status) . '</td>';  // Add status column
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">No logs found.</td></tr>';  // Updated colspan to 4

    }
    echo '</tbody></table></div>';
}


// Run commands

// Add a menu page in the WordPress dashboard
add_action('admin_menu', 'wpcli_executor_menu');
function wpcli_executor_menu() {
    add_menu_page(
        'Zype Import Executor',  // Page title
        'Zype Import Executor',  // Menu title
        'manage_options',        // Capability required
        'wpcli-executor',        // Slug for the page
        'wpcli_executor_page'    // Callback function to display content
    );
}

// Display the plugin page with command execution options
function wpcli_executor_page() {
    // Initialize variables with default values
    $page = isset($_POST['page']) ? sanitize_text_field($_POST['page']) : 1;
    $pages = isset($_POST['pages']) ? sanitize_text_field($_POST['pages']) : 1;
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'episode';
    $list_id = isset($_POST['list_id']) ? sanitize_text_field($_POST['list_id']) : '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Build the command dynamically based on user input
        $command = "zype import --page=$page --pages=$pages --post_type=$post_type --list_id=$list_id";
        
        // Validate the command and execute it
        if (in_array($post_type, ['event', 'episode'])) {
            $output = wpcli_execute_command($command);
        } else {
            $output = 'Invalid post type selected. Only "episode" and "event" are allowed.';
        }
    }
    ?>
    <div class="wrap">
        <h1>Zype Import Executor</h1>
        <form method="post">
            <label for="page">Page:</label>
            <input type="number" name="page" value="<?php echo esc_attr($page); ?>" min="1">
            <br><br>

            <label for="pages">Pages:</label>
            <input type="number" name="pages" value="<?php echo esc_attr($pages); ?>" min="1">
            <br><br>

            <label for="post_type">Post Type:</label>
            <select name="post_type">
                <option value="event" <?php selected($post_type, 'event'); ?>>Event</option>
                <option value="episode" <?php selected($post_type, 'episode'); ?>>Episode</option>
            </select>
            <br><br>

            <label for="list_id">List ID:</label>
            <input type="text" name="list_id" value="<?php echo esc_attr($list_id); ?>">
            <br><br>

            <input type="submit" value="Run Zype Import">
        </form>

        <?php if (isset($output)): ?>
            <h2>Output:</h2>
            <pre><?php echo esc_html($output); ?></pre>
        <?php endif; ?>
    </div>
    <?php
}

// Execute the WP-CLI command
function wpcli_execute_command($command) {
    // Ensure WP-CLI is available in the server's PATH
    $cmd = escapeshellcmd('wp ' . $command);
    $output = shell_exec($cmd);

    // Check if output indicates a failure
    if (strpos(strtolower($output), 'error') !== false || $output === null) {
        // Command failed, log the error with status "Failed"
        log_zype_import('Failed', $command, 0); // Assuming 0 videos were imported in case of failure
        return 'Command failed: ' . esc_html($output);
    } else {
        return $output;
    }
}

// Function to log the result of the import command
function log_zype_import($status, $command, $imported_videos) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'zype_import_logs';

    // Extract the post type from the command
    $matches = [];
    preg_match('/--post_type=([a-z]+)/', $command, $matches);
    $post_type = isset($matches[1]) ? $matches[1] : 'unknown';

    // Insert log into the table
    $wpdb->insert(
        $table_name,
        [
            'imported_at' => current_time('mysql'),
            'post_type' => $post_type,
            'imported_videos' => $imported_videos,
            'status' => $status,
        ]
    );
}