<?php

namespace DA\PluginActionsSafetyFeature;

/**
 * Handles mission-critical plugins to prevent their deactivation.
 *
 * This class contains functionality to disable the deactivation links
 * for specific plugins defined as mission-critical.
 */
class MissionCriticalPlugins
{
    /**
     * Gets the list of mission-critical plugins.
     *
     * @return array List of mission-critical plugin files.
     */
    protected static function get_critical_plugins()
    {
        // Default critical plugins
        $critical_plugins = [
            'wp-plugin-safety-check/wp-plugin-safety-feature.php'
        ];

        /**
         * Filter the list of mission-critical plugins.
         *
         * This allows users to modify the array of critical plugins.
         *
         * @param array $critical_plugins Array of plugin files.
         */
        return apply_filters('da_mission_critical_plugins', $critical_plugins);
    }

    /**
     * Modifies the action links for plugins on the plugins page.
     *
     * @param array $actions An array of plugin action links.
     * @param string $plugin_file Path to the plugin file relative to the plugins directory.
     * @return array Modified array of plugin action links.
     */
    public static function disable_deactivation($actions, $plugin_file)
    {
        $critical_plugins = self::get_critical_plugins();

        if (in_array($plugin_file, $critical_plugins, true)) {
            unset($actions['deactivate']);
        }

        return $actions;
    }

    /**
     * Prevents bulk actions from affecting mission-critical plugins and sets a transient.
     */
    public static function prevent_bulk_action_on_critical_plugins()
    {
        // Check if the current request is a bulk action on the plugins page
        if (isset($_POST['action']) && $_POST['action'] === 'deactivate-selected' && isset($_POST['checked'])) {
            $critical_plugins = self::get_critical_plugins();
            $intersect = array_intersect($_POST['checked'], $critical_plugins);

            if (!empty($intersect)) {
                // Set a transient to show the admin notice
                set_transient('da_bulk_action_critical_error', true, 10);
            }

            // Remove critical plugins from the array of plugins to be deactivated
            $_POST['checked'] = array_diff($_POST['checked'], $critical_plugins);
        }
    }

    /**
     * Display an admin notice if critical plugins were attempted to be bulk deactivated.
     */
    public static function display_admin_notice()
    {
        if (get_transient('da_bulk_action_critical_error')) {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo 'Bulk deactivation of mission-critical plugins is not allowed.';
            echo '</p></div>';
            delete_transient('da_bulk_action_critical_error');
        }
    }
}

add_filter('plugin_action_links', ['DA\PluginActionsSafetyFeature\MissionCriticalPlugins', 'disable_deactivation'], 10, 2);
add_action('load-plugins.php', ['DA\PluginActionsSafetyFeature\MissionCriticalPlugins', 'prevent_bulk_action_on_critical_plugins']);
add_action('admin_notices', ['DA\PluginActionsSafetyFeature\MissionCriticalPlugins', 'display_admin_notice']);
