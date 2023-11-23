<?php

namespace DA\PluginActionsSafetyFeature;

use DA\PluginActionsSafetyFeature\LogSearch;
use DA\PluginActionsSafetyFeature\LogCSVExporter;
use DA\PluginActionsSafetyFeature\LogPagination;

// Check if user has the right permission
if (!current_user_can('manage_options')) {
    return;
}

global $wpdb;
$table_name = $wpdb->prefix . 'plugin_actions_log';

// Get the current page number and number of items per page
$paged = isset($_GET['paged']) ? max(0, intval($_GET['paged']) - 1) : 0;
$per_page = get_option('dawp_plugin_actions_log_per_page', 10);

$search = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
$results = LogSearch::search($search, $per_page, $paged);
?>
<div class="wrap">
    <h1>Plugin Action Log</h1>
    <form method="post">
        <p class="search-box">
            <label class="screen-reader-text" for="plugin-search-input">Search Plugin Action Log:</label>
            <input type="search" id="plugin-search-input" name="s" value="<?php echo esc_attr($search); ?>">
            <input type="submit" id="search-submit" class="button" value="Search Plugin Action Log">
        </p>
    </form>
    <?php
        // Call to CSV Exporter
        $csvExporter = new LogCSVExporter();
        $csvExporter->exportButton();

        // Call to LogPurger
        LogPurger::render_purge_options_html();
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>User ID</th>
                <th>User Email</th>
                <th>Timestamp</th>
                <th>Plugin Name</th>
                <th>Plugin Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?php echo esc_html($row['user_id']); ?></td>
                    <td><?php echo esc_html($row['user_email']); ?></td>
                    <td><?php echo esc_html($row['timestamp']); ?></td>
                    <td><?php echo esc_html($row['plugin_name']); ?></td>
                    <td><?php echo esc_html($row['plugin_action']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    LogPagination::render($table_name);
?>
</div>
