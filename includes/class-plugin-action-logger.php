<?php

namespace DA\PluginActionsSafetyFeature;

/**
 * Handles logging of plugin activation and deactivation actions.
 *
 * This class provides functionality to log the activation and deactivation
 * of plugins to a database table. This is particularly useful for keeping
 * track of critical plugin state changes within the WordPress admin area.
 */

class PluginActionLogger
{
    /**
     * Logs the activation or deactivation of a plugin.
     *
     * This method is called via AJAX requests and logs the action
     * along with user and plugin details to the database.
     */
    public static function log_action()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'actions-safety-feature'));
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
}

// Register the actions
add_action('admin_init', function () {
    add_action('wp_ajax_log_plugin_activate', ['DA\PluginActionsSafetyFeature\PluginActionLogger', 'log_action']);
    add_action('wp_ajax_log_plugin_deactivate', ['DA\PluginActionsSafetyFeature\PluginActionLogger', 'log_action']);
});
