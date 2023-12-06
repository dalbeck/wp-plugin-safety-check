<?php

namespace DA\PluginActionsSafetyFeature;

/**
 * Class to handle log pagination functionality.
 */
class LogPagination
{
    /**
     * Renders pagination links for the Plugin Action Log page.
     *
     * @param string $table_name Name of the database table.
     */
    public static function render($table_name)
    {
        global $wpdb;

        // Ensure the table name is valid
        if (!preg_match('/^' . $wpdb->prefix . '[a-zA-Z0-9_]+$/', $table_name)) {
            // Handle the error - the provided table name is invalid
            return;
        }

        // Apply a filter to the number of items per page.
        $per_page = apply_filters('dawp_log_pagination_count', get_option('dawp_plugin_actions_log_per_page', 10));

        // Suppressing the nonce verification warning as it's unnecessary for simple pagination links
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $paged = isset($_GET['paged']) ? max(0, intval($_GET['paged']) - 1) : 0;

        // Construct the query safely, without placeholders for the table name
        $safe_table_name = esc_sql($table_name); // Sanitize table name
        $query = "SELECT COUNT(*) FROM `$safe_table_name`";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total_items = $wpdb->get_var($query);
        $total_pages = ceil($total_items / $per_page);

        echo '<div class="tablenav">';
        echo '<div class="tablenav-pages">';

        for ($i = 1; $i <= $total_pages; $i++) {
            $class = ($i === $paged + 1) ? ' class="current"' : '';
            echo "<a href=\"?page=plugin-action-log&paged={$i}\"{$class}>{$i}</a> ";
        }

        echo '</div>';
        echo '</div>';
    }
}
