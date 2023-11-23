<?php

/**
 * Exports data to a CSV file and initiates a download.
 *
 * @param array $data The data to be exported.
 * @param string $filename The name of the file to be downloaded.
 */

namespace DA\PluginActionsSafetyFeature;

class LogCSVExporter
{
    public static function export(array $data) {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = 'plugin-actions-log_' . $timestamp . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }
    /**
     * Renders the export button for CSV export.
     */
    public function exportButton() {
        echo '<button id="export-csv" class="button">Export to CSV</button>';
    }
}
