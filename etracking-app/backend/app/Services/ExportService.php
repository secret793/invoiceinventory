<?php

namespace App\Services;

/**
 * Simple CSV export service (no external dependency).
 * Can be extended with PhpSpreadsheet when available.
 */
class ExportService
{
    public static function toCsv(array $rows, array $headers = []): string
    {
        if (empty($rows)) return '';

        $output = fopen('php://temp', 'w+');

        $cols = $headers ?: array_keys($rows[0]);
        fputcsv($output, $cols);

        foreach ($rows as $row) {
            $line = [];
            foreach ($cols as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($output, $line);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    public static function streamCsv(array $rows, string $filename, array $headers = []): void
    {
        $csv = self::toCsv($rows, $headers);
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Content-Length: ' . strlen($csv));
        echo $csv;
        exit;
    }

    public static function toJson(array $rows): string
    {
        return json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
