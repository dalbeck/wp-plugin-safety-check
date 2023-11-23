<?php

/**
 * Handles the uninstallation process of the Plugin Actions Safety Feature plugin.
 *
 * This class is responsible for cleaning up the database and settings
 * when the plugin is uninstalled. It ensures that all plugin-specific data,
 * including database tables and stored options, are removed from the WordPress
 * site, maintaining a clean environment after the plugin is no longer in use.
 */

namespace DA\PluginActionsSafetyFeature;

class Uninstaller
{
    /**
     * Code to run during plugin uninstallation.
     *
     * This method handles the removal of plugin-specific database tables
     * and options upon uninstallation of the plugin.
     */
    public static function uninstall()
    {
        global $wpdb;

        // Remove the plugin's database table
        $table_name = $wpdb->prefix . 'plugin_actions_log';
        $sql = "DROP TABLE IF EXISTS {$table_name};";
        $wpdb->query($sql);

        // Delete any options or transients related to the plugin
        delete_option('dawp_plugin_actions_log_table_created');
    }
}
