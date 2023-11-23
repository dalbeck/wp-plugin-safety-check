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

        // Apply a filter to the number of items per page.
        $per_page = apply_filters('dawp_log_pagination_count', get_option('dawp_plugin_actions_log_per_page', 10));

        $paged = isset($_GET['paged']) ? max(0, intval($_GET['paged']) - 1) : 0;
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
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
