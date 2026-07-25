<?php

namespace App\Services;

use Generator;
use RuntimeException;

/**
 * Small, dependency-free reader for the import formats supported by the planner.
 * CSV files are read row-by-row. XLSX files are read from the first worksheet and
 * exposed through the same row/line contract so importers can validate consistently.
 */
class SpreadsheetImportService
{
    public function rows(string $path, string $extension, callable $normalizeHeader): Generator
    {
        $extension = strtolower(ltrim($extension, '.'));

        if (in_array($extension, ['csv', 'txt'], true)) {
            yield from $this->csvRows($path, $normalizeHeader);

            return;
        }

        if ($extension === 'xlsx') {
            yield from $this->xlsxRows($path, $normalizeHeader);

            return;
        }

        throw new RuntimeException('Only CSV and Excel (.xlsx) files are supported.');
    }

    private function csvRows(string $path, callable $normalizeHeader): Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The uploaded CSV file could not be read.');
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false || count(array_filter($headers, fn ($header) => trim((string) $header) !== '')) === 0) {
                throw new RuntimeException('The file must include a header row.');
            }

            $headers = array_map($normalizeHeader, $headers);
            $this->assertUniqueHeaders($headers);

            $line = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $line++;
                $values = array_pad($values, count($headers), '');
                $values = array_slice($values, 0, count($headers));
                $data = array_combine($headers, $values);

                if (is_array($data) && $this->hasValues($data)) {
                    yield ['line' => $line, 'data' => $data];
                }
            }
        } finally {
            fclose($handle);
        }
    }

    private function xlsxRows(string $path, callable $normalizeHeader): Generator
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('The Excel workbook could not be opened.');
        }

        try {
            $sharedStrings = [];
            if (($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                $shared = simplexml_load_string($sharedXml);
                if ($shared === false) {
                    throw new RuntimeException('The Excel shared strings are invalid.');
                }
                $namespace = $shared->getDocNamespaces(true)[''] ?: 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                $shared->registerXPathNamespace('x', $namespace);
                foreach ($shared->xpath('//x:si') ?: [] as $item) {
                    $item->registerXPathNamespace('x', $namespace);
                    $sharedStrings[] = implode('', array_map('strval', $item->xpath('.//x:t') ?: []));
                }
            }

            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml === false) {
                throw new RuntimeException('The Excel workbook has no first worksheet.');
            }
        } finally {
            $zip->close();
        }

        $sheet = simplexml_load_string($sheetXml);
        if ($sheet === false) {
            throw new RuntimeException('The Excel worksheet is invalid.');
        }
        $namespace = $sheet->getDocNamespaces(true)[''] ?: 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sheet->registerXPathNamespace('x', $namespace);

        $headers = null;
        foreach ($sheet->xpath('//x:sheetData/x:row') ?: [] as $rowIndex => $row) {
            $row->registerXPathNamespace('x', $namespace);
            $values = [];
            foreach ($row->xpath('./x:c') ?: [] as $cell) {
                $cell->registerXPathNamespace('x', $namespace);
                preg_match('/^[A-Z]+/', (string) $cell['r'], $match);
                $column = $this->excelColumnNumber($match[0] ?? 'A');
                $value = (string) $cell->v;
                $cellType = (string) $cell['t'];

                if ($cellType === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($cellType === 'inlineStr') {
                    $value = implode('', array_map('strval', $cell->xpath('./x:is//x:t') ?: []));
                }

                $values[$column] = $value;
            }

            if ($values === []) {
                continue;
            }

            $rowValues = array_fill(0, max(array_keys($values)) + 1, '');
            foreach ($values as $column => $value) {
                $rowValues[$column] = $value;
            }

            if ($headers === null) {
                $headers = array_map($normalizeHeader, $rowValues);
                $this->assertUniqueHeaders($headers);

                continue;
            }

            $rowValues = array_pad($rowValues, count($headers), '');
            $data = array_combine($headers, array_slice($rowValues, 0, count($headers)));
            if (is_array($data) && $this->hasValues($data)) {
                yield ['line' => $rowIndex + 1, 'data' => $data];
            }
        }

        if ($headers === null || $headers === []) {
            throw new RuntimeException('The file must include a header row.');
        }
    }

    private function assertUniqueHeaders(array $headers): void
    {
        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        if (in_array('', $headers, true)) {
            throw new RuntimeException('Every column must have a heading.');
        }
        if (count($headers) !== count(array_unique($headers))) {
            throw new RuntimeException('The file contains duplicate column headings.');
        }
    }

    private function hasValues(array $data): bool
    {
        return array_filter($data, fn ($value) => trim((string) $value) !== '') !== [];
    }

    private function excelColumnNumber(string $letters): int
    {
        $number = 0;
        foreach (str_split($letters) as $letter) {
            $number = ($number * 26) + ord($letter) - 64;
        }

        return $number - 1;
    }
}
