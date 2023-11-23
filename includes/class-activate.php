<?php

/**
 * Handles tasks to run during plugin activation.
 *
 * This method checks if the plugin's database table has already been created.
 * If not, it creates the table and sets an option to indicate that the table
 * has been created. This ensures that the table creation logic runs only once
 * during the plugin's first activation.
 */

namespace DA\PluginActionsSafetyFeature;

class Activate
{
    /**
     * Code to run during plugin activation.
     */
    public static function activate()
    {
        if (!get_option('dawp_plugin_actions_log_table_created')) {
            self::create_plugin_actions_log_table();
            update_option('dawp_plugin_actions_log_table_created', true);
        }
    }

    /**
     * Creates the plugin actions log table in the database.
     */
    private static function create_plugin_actions_log_table()
    {
        global $wpdb;

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
    }
}
