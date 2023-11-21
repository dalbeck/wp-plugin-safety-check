<?php
/*
Plugin Name: Plugin Actions Safetey Feature
Description: Adds a warning modal when a user clicks the deactivate or activate link for a plugin. Also logs the activation or deactivation of a plugin in a database table with ability to export to CSV. This plugin is intended to be used on a live site to prevent accidental deactivation of "mission critical" plugins.
Author: Danny Albeck
Author URI: https://www.albeckconsulting.com
Version: 1.0.0
Text Domain: plugin-actions-safety-feature
Domain Path: /languages
*/

/**
 * Initialize the plugin text domain for translation.
 */
function da_plugin_actions_safety_feature_load_textdomain()
{
    load_plugin_textdomain('plugin-actions-safety-feature', false, basename(dirname(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'da_plugin_actions_safety_feature_load_textdomain');

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

function da_enqueue_scripts()
{
    $screen = get_current_screen();

    // Check if the current screen is the plugins page
    if (isset($screen->base) && $screen->base == 'plugins') {
        wp_enqueue_script('da-custom-script', plugin_dir_url(__FILE__) . 'js/app.js', array('jquery'), null, true);

        // Create a nonce and pass it to the script
        wp_localize_script('da-custom-script', 'da_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('da_nonce_action')
        ));
    }
}
add_action('admin_enqueue_scripts', 'da_enqueue_scripts');

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
function da_disable_deactivation($actions, $plugin_file)
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
add_filter('plugin_action_links', 'da_disable_deactivation', 10, 4);

/**
 * Creates a new database table to log interactions with the plugin deactivation.
 *
 * @return void
 */

function da_create_plugin_actions_log_table()
{
    // Check if the table has already been created
    if (get_option('da_plugin_actions_log_table_created') == false) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'plugin_actions_log';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            user_email varchar(100) NOT NULL,
            timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            plugin_name text NOT NULL,
            plugin_action varchar(20) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Set the option to indicate that the table has been created
        update_option('da_plugin_actions_log_table_created', true);
    }
}
add_action('init', 'da_create_plugin_actions_log_table');

/**
 * Logs the activation or deactivation of a plugin.
 *
 * @return void
 */

function da_plugin_action_log()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'plugin-actions-safety-feature'));
    }

    check_ajax_referer('da_nonce_action', 'nonce');

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
add_action('wp_ajax_log_plugin_activate', 'da_plugin_action_log');
add_action('wp_ajax_log_plugin_deactivate', 'da_plugin_action_log');


/**
 * Adds a new submenu page under the Plugins menu.
 *
 * @return void
 */
function da_add_plugin_actions_log_page()
{
    add_submenu_page(
        'plugins.php',
        'Plugin Action Log',
        'Plugin Action Log',
        'manage_options',
        'plugin-action-log',
        'da_display_plugin_actions_log_page'
    );
}
add_action('admin_menu', 'da_add_plugin_actions_log_page');

/**
 * Displays the Plugin Action Log page.
 *
 * @return void
 */
function da_display_plugin_actions_log_page()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'plugin_actions_log';

    $search = (isset($_POST['s'])) ? sanitize_text_field($_POST['s']) : '';

    $query = "SELECT * FROM $table_name";
    if ($search) {
        $query .= $wpdb->prepare(" WHERE user_id LIKE '%%%s%%' OR user_email LIKE '%%%s%%' OR timestamp LIKE '%%%s%%' OR plugin_name LIKE '%%%s%%' OR plugin_action LIKE '%%%s%%'", $search, $search, $search, $search, $search);
    }
    $query .= " ORDER BY timestamp DESC";

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
