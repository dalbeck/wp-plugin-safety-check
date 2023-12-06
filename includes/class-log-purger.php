<?php

namespace DA\PluginActionsSafetyFeature;

/**
 * Class LogPurger
 *
 * Handles the functionality for purging plugin action logs in the database.
 *
 * This class provides methods to purge all logs or purge logs except for the last 30 days.
 * It can be invoked via AJAX requests from the Plugin Action Log admin page.
 *
 * Usage:
 * - `LogPurger::purge_all()` to purge all logs.
 * - `LogPurger::purge_except_last_30_days()` to purge all logs except those from the last 30 days.
 *
 * @package DA\PluginActionsSafetyFeature
 */
class LogPurger
{
    /**
     * Renders the HTML for the log purge options select box.
     *
     * @return void
     */
    public static function render_purge_options_html()
    {
        echo '<select id="log-purge-options">
                  <option value="purge_all">Purge All</option>
                  <option value="purge_except_last_30_days">Purge All Except Last 30 Days</option>
              </select>
              <button id="execute-log-purge" class="button">Purge Logs</button>';
    }

    /**
     * Handles the AJAX request for purging log entries.
     */
    public static function handle_log_purge_request()
    {
        check_ajax_referer('dawp_purge_nonce', 'nonce');

        $purge_option = sanitize_text_field($_POST['purge_option']);

        if ($purge_option === 'purge_all') {
            self::purge_all_logs();
        } elseif ($purge_option === 'purge_except_last_30_days') {
            self::purge_except_last_30_days();
        }

        wp_send_json_success('Logs purged successfully.');
    }

    /**
     * Purge all logs from the database.
     */
    protected static function purge_all_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'plugin_actions_log';

        // Ensure the table name is valid
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query("TRUNCATE TABLE `{$table_name}`");
        } else {
            // Handle the error - table name is not valid
            error_log("Error: Attempted to truncate an invalid table: {$table_name}");
        }
    }

    /**
     * Purge all logs except the last 30 days.
     */
    protected static function purge_except_last_30_days() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'plugin_actions_log';

        // Calculate the date 30 days ago in MySQL format
        $date_30_days_ago = gmdate('Y-m-d H:i:s', strtotime(current_time('mysql') . ' -30 days'));

        // Use $wpdb->delete for a safe query execution
        $wpdb->delete($table_name, array('timestamp' => $date_30_days_ago), array('%s'));
    }

}

// Register AJAX actions
add_action('wp_ajax_purge_plugin_action_logs', ['DA\PluginActionsSafetyFeature\LogPurger', 'handle_log_purge_request']);
