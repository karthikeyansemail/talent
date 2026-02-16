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
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Exception $e) {
            return '';
        }
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
}
