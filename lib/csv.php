<?php
declare(strict_types=1);

function csv_cell_value(mixed $value): string
{
    $value = (string) $value;

    // Spreadsheet programs may interpret values beginning with these
    // characters as formulas when a CSV is opened.
    if (preg_match('/^[\t\r\n ]*[=+\-@]/', $value) === 1) {
        return "'" . $value;
    }

    return $value;
}

function send_csv_download(string $filename, array $headers, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        http_response_code(500);
        exit('Could not open output stream.');
    }

    // Help Excel identify the export as UTF-8 while keeping the file valid CSV.
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        $line = [];
        foreach ($headers as $header) {
            $line[] = csv_cell_value($row[$header] ?? '');
        }
        fputcsv($output, $line);
    }

    fclose($output);
    exit;
}
