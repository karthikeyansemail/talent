<?php

namespace App\Services;

class DocumentTextExtractor
{
    /**
     * Extract text from a PDF or DOCX file.
     */
    public function extract(string $filePath, string $extension): string
    {
        $text = match (strtolower($extension)) {
            'pdf' => $this->extractPdf($filePath),
            'docx' => $this->extractDocx($filePath),
            default => '',
        };

        // Ensure valid UTF-8 — PDF extraction can produce malformed bytes
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    private function extractPdf(string $filePath): string
    {
        // Try Smalot\PdfParser first
        $text = '';
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
        } catch (\Exception $e) {
            // fall through to pdftotext
        }

        // If Smalot extraction looks poor (too short or missing key content),
        // fall back to pdftotext CLI which handles complex PDFs better
        if (strlen(trim($text)) < 200 || substr_count($text, '@') === 0) {
            $pdftotext = $this->extractPdfViaCli($filePath);
            if (strlen(trim($pdftotext)) > strlen(trim($text))) {
                return $pdftotext;
            }
        }

        return $text;
    }

    private function extractPdfViaCli(string $filePath): string
    {
        // Try system pdftotext (Poppler/Xpdf)
        $candidates = [
            'C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe',
            'pdftotext',
        ];

        foreach ($candidates as $bin) {
            try {
                $cmd = sprintf('%s %s -', escapeshellarg($bin), escapeshellarg($filePath));
                $output = null;
                $exitCode = null;
                exec($cmd . ' 2>&1', $output, $exitCode);
                if ($exitCode === 0 && !empty($output)) {
                    return implode("\n", $output);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return '';
    }

    private function extractDocx(string $filePath): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($xml) {
                    $dom = new \DOMDocument();
                    $dom->loadXML($xml);
                    return strip_tags($dom->saveXML());
                }
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract the first email address found in text using regex.
     * Useful as a fallback when AI parsing misses the email.
     */
    public static function extractEmail(string $text): ?string
    {
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            return strtolower($matches[0]);
        }
        return null;
    }
}
