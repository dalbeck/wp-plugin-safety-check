<?php

/**
 * Plugin Name: Plugin Actions Safety Feature
 * Description: Adds a warning modal when a user clicks the deactivate or activate link for a plugin...
 * Author: Danny Albeck
 * Author URI: https://www.albeckconsulting.com
 * Version: 1.1.0
 * Text Domain: plugin-actions-safety-feature
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin version constant.
define('PLUGIN_ACTION_SAFETY_FEATURE_VERSION', '1.1.0');

// Define the base URL for the plugin.
define('PLUGIN_ACTION_SAFETY_FEATURE_URL', plugin_dir_url(__FILE__));

// Automatically include class files from the 'includes' directory
$includes_dir = plugin_dir_path(__FILE__) . 'includes/';
foreach (glob($includes_dir . 'class-*.php') as $file) {
    require_once $file;
}

// Activation hook.
register_activation_hook(__FILE__, array('DA\PluginActionsSafetyFeature\Activate', 'activate'));

// Register the uninstall function.
if (function_exists('register_uninstall_hook')) {
    register_uninstall_hook(__FILE__, array('DA\PluginActionsSafetyFeature\Uninstaller', 'uninstall'));
}

// Initialize the plugin.
function run_plugin_action_safety_feature()
{
    $plugin = new DA\PluginActionsSafetyFeature\PluginActionSafety();
    $plugin->run();
}
run_plugin_action_safety_feature();
