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

        // Initialize the WHERE clauses array and parameters array
        $whereClauses = [];
        $query_params = [];

        // Prepare the search query
        if ($search) {
            // Escaping for LIKE search
            $like_search = '%' . $wpdb->esc_like($search) . '%';
            $whereClauses[] = "user_id LIKE %s";
            $query_params[] = $like_search;

            $whereClauses[] = "user_email LIKE %s";
            $query_params[] = $like_search;

            $whereClauses[] = "plugin_name LIKE %s";
            $query_params[] = $like_search;

            // Exact match for plugin_action
            if (in_array($search, ['activate', 'deactivate'], true)) {
                $whereClauses[] = "plugin_action = %s";
                $query_params[] = $search;
            } else {
                $whereClauses[] = "plugin_action LIKE %s";
                $query_params[] = $like_search;
            }

            // Check for date format
            if (strtotime($search) !== false) {
                $whereClauses[] = "timestamp LIKE %s";
                $query_params[] = $like_search;
            }
        }

        // Build the base query
        $query = "SELECT * FROM $table_name";

        // Append WHERE clauses if they exist
        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' OR ', $whereClauses);
        }

        // Add ordering and pagination
        $query .= " ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $query_params[] = $per_page;
        $query_params[] = $paged * $per_page;

        // Prepare the full query
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $prepared_query = $wpdb->prepare($query, $query_params);

        // Execute the query and return results
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results($prepared_query, ARRAY_A);
    }
}
