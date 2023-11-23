<?php

namespace DA\PluginActionsSafetyFeature;

/**
 * Handles searching within the plugin actions log.
 */
class LogSearch
{

    /**
     * Performs a search on the plugin actions log table.
     *
     * @param string $search The search term.
     * @param int $per_page Number of items per page.
     * @param int $paged Current page number.
     * @return array The search results.
     */
    public static function search($search, $per_page, $paged)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'plugin_actions_log';

        // Initialize the WHERE clauses array
        $whereClauses = [];

        // Prepare the search query
        if ($search) {
            $whereClauses[] = $wpdb->prepare("user_id LIKE '%%%s%%'", $search);
            $whereClauses[] = $wpdb->prepare("user_email LIKE '%%%s%%'", $search);
            $whereClauses[] = $wpdb->prepare("plugin_name LIKE '%%%s%%'", $search);
            $whereClauses[] = $wpdb->prepare("plugin_action LIKE '%%%s%%'", $search);

            // Check for date format
            if (strtotime($search) !== false) {
                $whereClauses[] = $wpdb->prepare("timestamp LIKE '%%%s%%'", $search);
            }
        }

        // Build the query
        $query = "SELECT * FROM $table_name";
        if (!empty($whereClauses)) {
            $query .= " WHERE " . join(' OR ', $whereClauses);
        }
        $query .= " ORDER BY timestamp DESC";
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $paged * $per_page);

        // Execute the query and return results
        return $wpdb->get_results($query, ARRAY_A);
    }
}
