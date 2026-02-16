<?php

namespace App\Services;

class SpreadsheetParser
{
    /**
     * Parse a CSV or XLSX file and return an array of rows.
     * Each row is an associative array keyed by the header columns.
     */
    public function parse(string $filePath, string $extension): array
    {
        return match ($extension) {
            'csv' => $this->parseCsv($filePath),
            'xlsx' => $this->parseXlsx($filePath),
            default => throw new \InvalidArgumentException("Unsupported file type: {$extension}"),
        };
    }

    private function parseCsv(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Cannot open CSV file.');
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new \RuntimeException('CSV file is empty or has no headers.');
        }

        // Normalize headers: trim, lowercase, replace spaces with underscores
        $headers = array_map(function ($h) {
            return strtolower(trim(str_replace(' ', '_', $h)));
        }, $headers);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                continue; // skip malformed rows
            }
            $row = array_combine($headers, $data);
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function parseXlsx(string $filePath): array
    {
        $rows = [];
        $zip = new \ZipArchive();

        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Cannot open XLSX file.');
        }

        // Read shared strings
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $ssDoc = new \DOMDocument();
            $ssDoc->loadXML($ssXml);
            $siNodes = $ssDoc->getElementsByTagName('si');
            foreach ($siNodes as $si) {
                $text = '';
                $tNodes = $si->getElementsByTagName('t');
                foreach ($tNodes as $t) {
                    $text .= $t->textContent;
                }
                $sharedStrings[] = $text;
            }
        }

        // Read first sheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            $zip->close();
            throw new \RuntimeException('Cannot find worksheet in XLSX file.');
        }

        $sheetDoc = new \DOMDocument();
        $sheetDoc->loadXML($sheetXml);
        $rowNodes = $sheetDoc->getElementsByTagName('row');

        $headers = [];
        $firstRow = true;

        foreach ($rowNodes as $rowNode) {
            $cells = $rowNode->getElementsByTagName('c');
            $rowData = [];

            foreach ($cells as $cell) {
                $value = '';
                $vNode = $cell->getElementsByTagName('v')->item(0);
                if ($vNode) {
                    $value = $vNode->textContent;
                    // Check if it's a shared string
                    if ($cell->getAttribute('t') === 's') {
                        $value = $sharedStrings[(int) $value] ?? $value;
                    }
                }

                // Extract column letter from cell reference (e.g., "A1" -> "A")
                $ref = $cell->getAttribute('r');
                $col = preg_replace('/\d+/', '', $ref);
                $colIndex = $this->columnToIndex($col);
                $rowData[$colIndex] = $value;
            }

            if ($firstRow) {
                // Use first row as headers
                ksort($rowData);
                $headers = array_map(function ($h) {
                    return strtolower(trim(str_replace(' ', '_', $h)));
                }, $rowData);
                $firstRow = false;
                continue;
            }

            if (empty($headers)) {
                continue;
            }

            // Build associative row
            $assocRow = [];
            foreach ($headers as $idx => $header) {
                $assocRow[$header] = $rowData[$idx] ?? '';
            }
            $rows[] = $assocRow;
        }

        $zip->close();
        return $rows;
    }

    /**
     * Convert Excel column letter to 0-based index (A=0, B=1, ..., Z=25, AA=26, etc.)
     */
    private function columnToIndex(string $col): int
    {
        $col = strtoupper($col);
        $index = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }
}
