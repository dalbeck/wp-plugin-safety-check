<?php
/*
Plugin Name: Plugin Actions Safetey Feature
Description: Adds a warning modal when a user clicks the deactivate or activate link for a plugin. Also logs the activation or deactivation of a plugin in a database table with ability to export to CSV. This plugin is intended to be used on a live site to prevent accidental deactivation of "mission critical" plugins.
Author: Danny Albeck
Author URI: https://www.albeckconsulting.com
Version: 1.0.1
Text Domain: plugin-actions-safety-feature
Domain Path: /languages
*/

/**
 * Initialize the plugin text domain for translation.
 */
function dawp_plugin_actions_safety_feature_load_textdomain()
{
    load_plugin_textdomain('plugin-actions-safety-feature', false, basename(dirname(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'dawp_plugin_actions_safety_feature_load_textdomain');

/**
 * Enqueues a custom script and localizes it for use on the 'plugins.php' admin page.
 *
 * This function is hooked into the 'admin_enqueue_scripts' action and is responsible
 * for enqueuing the 'da-custom-script' JavaScript file, which is only loaded on the
 * 'plugins.php' page in the WordPress admin area. It also localizes the script, passing
 * the URL for AJAX requests and a security nonce for use in AJAX calls to ensure the
 * request is valid and coming from the correct place.
 *
 * @global string $pagenow The name of the current admin page.
 */

function dawp_enqueue_scripts()
{
    global $pagenow;

    // Check if the current page is plugins.php or the plugin-action-log page
    if ($pagenow == 'plugins.php' && (empty($_GET['page']) || $_GET['page'] == 'plugin-action-log')) {
        wp_enqueue_script('wp-custom-script', plugin_dir_url(__FILE__) . 'js/app.js', null, null, true);
        wp_localize_script('wp-custom-script', 'wp_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_nonce_action')
        ));
        wp_enqueue_style('wp-custom-style', plugin_dir_url(__FILE__) . 'css/app.css');
    }
}
add_action('admin_enqueue_scripts', 'dawp_enqueue_scripts');


/**
 * Disables the deactivation link for "mission critical" plugins.
 *
 * @param array  $actions     An array of plugin action links. By default this can include 'activate',
 *                            'deactivate', and 'delete'. With Multisite active this can also include
 *                            'network_active' and 'network_only' items.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 *
 * @return array $actions     Modified array of plugin action links.
 */
function dawp_disable_deactivation($actions, $plugin_file)
{
    // Array of "mission critical" plugins - Define your plugins here to fully remove plugin actions
    $critical_plugins = array(
        'advanced-custom-fields-pro/acf.php',
        'gravityforms/gravityforms.php',
    );

    // Loop through the array of critical plugins
    foreach ($critical_plugins as $critical_plugin) {
        // If the current plugin is a critical plugin, remove the deactivate link
        if ($critical_plugin === $plugin_file) {
            unset($actions['deactivate']);
            break;
        }
    }

    return $actions;
}
add_filter('plugin_action_links', 'dawp_disable_deactivation', 10, 4);

/**
 * Creates a new database table to log interactions with the plugin deactivation.
 *
 * @return void
 */

function dawp_create_plugin_actions_log_table()
{
    global $wpdb;

    // Check if the table creation flag is already set
    if (get_option('dawp_plugin_actions_log_table_created')) {
        return;
    }

    $table_name = $wpdb->prefix . 'plugin_actions_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        user_email varchar(100) NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        plugin_name varchar(255) NOT NULL,
        plugin_action varchar(20) NOT NULL,
        PRIMARY KEY  (id),
        INDEX idx_user_id (user_id),
        INDEX idx_plugin_name (plugin_name(191)),
        INDEX idx_timestamp (timestamp)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Set the option that the table has been created
    update_option('dawp_plugin_actions_log_table_created', true);
}
add_action('admin_init', 'dawp_create_plugin_actions_log_table');

/**
 * Logs the activation or deactivation of a plugin.
 *
 * @return void
 */

function dawp_plugin_action_log()
{
    if (!current_user_can('manage_options')) {
        dawp_die(__('You do not have sufficient permissions to access this page.', 'plugin-actions-safety-feature'));
    }

    check_ajax_referer('wp_nonce_action', 'nonce');

    global $wpdb;

    $table_name = $wpdb->prefix . 'plugin_actions_log';

    $wpdb->insert(
        $table_name,
        array(
            'user_id' => get_current_user_id(),
            'user_email' => sanitize_email(wp_get_current_user()->user_email),
            'timestamp' => current_time('mysql'),
            'plugin_name' => sanitize_text_field($_POST['plugin_name']),
            'plugin_action' => sanitize_key($_POST['plugin_action'])
        )
    );

    wp_die();
}
add_action('wp_ajax_log_plugin_activate', 'dawp_plugin_action_log');
add_action('wp_ajax_log_plugin_deactivate', 'dawp_plugin_action_log');


/**
 * Adds a new submenu page under the Plugins menu.
 *
 * @return void
 */
function dawp_add_plugin_actions_log_page()
{
    add_submenu_page(
        'plugins.php',
        'Plugin Action Log',
        'Plugin Action Log',
        'manage_options',
        'plugin-action-log',
        'dawp_display_plugin_actions_log_page'
    );
}
add_action('admin_menu', 'dawp_add_plugin_actions_log_page');

/**
 * Displays the Plugin Action Log page.
 *
 * @return void
 */
function dawp_display_plugin_actions_log_page()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'plugin_actions_log';

    // Get the current page number
    $paged = isset($_GET['paged']) ? max(0, intval($_GET['paged']) - 1) : 0;

    // Number of items per page
    $per_page = get_option('dawp_plugin_actions_log_per_page', 10);

    // Calculate the total number of pages and the offset
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);
    $offset = $paged * $per_page;

    // Fetch the items for the current page
    $search = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';

    $query = "SELECT * FROM $table_name";
    if ($search) {
        $query .= $wpdb->prepare(" WHERE user_id LIKE '%%%s%%' OR user_email LIKE '%%%s%%' OR timestamp LIKE '%%%s%%' OR plugin_name LIKE '%%%s%%' OR plugin_action LIKE '%%%s%%'", $search, $search, $search, $search, $search);
    }
    $query .= $wpdb->prepare(" ORDER BY timestamp DESC LIMIT %d OFFSET %d", $per_page, $offset);

    $results = $wpdb->get_results($query, ARRAY_A);

    echo '<div class="wrap">';
        echo '<h1>Plugin Action Log</h1>';
        echo '<form method="post">';
        echo '<p class="search-box">';
            echo '<label class="screen-reader-text" for="plugin-search-input">Search Plugin Action Log:</label>';
            echo '<input type="search" id="plugin-search-input" name="s" value="' . esc_attr($search) . '">';
            echo '<input type="submit" id="search-submit" class="button" value="Search Plugin Action Log"></p>';
        echo '</form>';
        echo '<button id="export-csv" class="button">Export to CSV</button>';
        echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>User ID</th><th>User Email</th><th>Timestamp</th><th>Plugin Name</th><th>Plugin Action</th></tr></thead>';
            echo '<tbody>';
            foreach ($results as $row) {
                echo '<tr>';
                echo '<td>' . esc_html($row['user_id']) . '</td>';
                echo '<td>' . esc_html($row['user_email']) . '</td>';
                echo '<td>' . esc_html($row['timestamp']) . '</td>';
                echo '<td>' . esc_html($row['plugin_name']) . '</td>';
                echo '<td>' . esc_html($row['plugin_action']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
        echo '</table>';
            // Add pagination links
            echo '<div class="tablenav">';
                echo '<div class="tablenav-pages">';
                for ($i = 1; $i <= $total_pages; $i++) {
                    echo '<a href="?page=plugin-action-log&paged=' . $i . '"' . ($i === $paged + 1 ? ' class="current"' : '') . '>' . $i . '</a> ';
                }
                echo '</div>';
            echo '</div>';
    echo '</div>';

    // JavaScript code for exporting the table data to CSV
    echo "
    <script>
    jQuery(document).ready(function($) {
        $('#export-csv').click(function(e) {
            e.preventDefault();

            var data = [['User ID', 'User Email', 'Timestamp', 'Plugin Name', 'Plugin Action']];

            $('.wp-list-table tbody tr').each(function() {
                var row = [];
                $(this).find('td').each(function() {
                    row.push($(this).text());
                });
                data.push(row);
            });

            var csv = '';
            data.forEach(function(row) {
                csv += row.join(',');
                csv += '\\n';
            });

            var date = new Date();
            var timestamp = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + '_' + date.getHours() + '-' + date.getMinutes() + '-' + date.getSeconds();

            var hiddenElement = document.createElement('a');
            hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
            hiddenElement.target = '_blank';
            hiddenElement.download = 'plugin-actions-log_' + timestamp + '.csv';
            hiddenElement.click();
        });
    });
    </script>
    ";
}

/**
 * Handles the uninstallation of the plugin.
 *
 * This function is triggered when the plugin is uninstalled from the WordPress admin dashboard.
 * It removes the '_plugin_actions_log' table from the database, cleaning up the data created by the plugin.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */

function dawp_uninstall_plugin()
{
    global $wpdb;

    // Table name
    $table_name = $wpdb->prefix . 'plugin_actions_log';

    // SQL to delete table
    $sql = "DROP TABLE IF EXISTS {$table_name};";

    // Execute the query
    $wpdb->query($sql);

    // Delete the option indicating the table was created
    delete_option('dawp_plugin_actions_log_table_created');

}

// Register the uninstall hook
register_uninstall_hook(__FILE__, 'dawp_uninstall_plugin');
