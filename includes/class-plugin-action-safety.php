<?php

namespace DA\PluginActionsSafetyFeature;

class PluginActionSafety
{
    /**
     * Run the plugin.
     */
    public function run()
    {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_plugin_actions_log_page'));
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('plugin-actions-safety-feature', false, basename(dirname(__FILE__, 2)) . '/languages');
    }

    /**
     * Enqueue scripts and styles for the admin area.
     */
    public function enqueue_scripts($hook)
    {
        global $pagenow;

        // Enqueue modal.js only on the plugins.php page
        if ($pagenow == 'plugins.php' && empty($_GET['page'])) {
            $modal_timeout = apply_filters('dawp_modal_timeout', 10000); // Default is 10000 milliseconds

            wp_enqueue_script('dawp-modal', PLUGIN_ACTION_SAFETY_FEATURE_URL . 'assets/js/dawp-modal.js', null, PLUGIN_ACTION_SAFETY_FEATURE_VERSION, true);

            wp_localize_script('dawp-modal', 'dawpModalData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_nonce_action'),
                'timeout' => $modal_timeout
            ));
        }

        // Enqueue csvexport.js only on the plugin action log page
        if ($pagenow == 'plugins.php' && isset($_GET['page']) && $_GET['page'] == 'plugin-action-log') {
            wp_enqueue_script('dawp-csv-export', PLUGIN_ACTION_SAFETY_FEATURE_URL . 'assets/js/dawp-csvexport.js', array(), PLUGIN_ACTION_SAFETY_FEATURE_VERSION, true);

            wp_enqueue_script('dawp-log-purge', PLUGIN_ACTION_SAFETY_FEATURE_URL . 'assets/js/dawp-logPurger.js', array(), PLUGIN_ACTION_SAFETY_FEATURE_VERSION, true);
            wp_localize_script('dawp-log-purge', 'dawpPurgeData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dawp_purge_nonce')
            ));
        }

        // Enqueue styles on both pages
        if ($pagenow == 'plugins.php' && (empty($_GET['page']) || $_GET['page'] == 'plugin-action-log')) {
            wp_enqueue_style('dawp-custom-style', PLUGIN_ACTION_SAFETY_FEATURE_URL . 'assets/css/dawp-app.css', array(), PLUGIN_ACTION_SAFETY_FEATURE_VERSION);
        }
    }

    /**
     * Adds a new submenu page under the Plugins menu.
     */
    public function add_plugin_actions_log_page()
    {
        add_submenu_page(
            'plugins.php',
            'Plugin Action Log',
            'Plugin Action Log',
            'manage_options',
            'plugin-action-log',
            array($this, 'display_plugin_actions_log_page')
        );
    }

    /**
     * Displays the Plugin Action Log page.
     */
    public function display_plugin_actions_log_page()
    {
        // Include the view for the admin page.
        require_once plugin_dir_path(__FILE__) . '../views/admin/plugin-actions-log-page.php';
    }

}
