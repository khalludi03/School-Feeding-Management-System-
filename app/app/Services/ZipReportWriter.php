<?php

namespace App\Services;

use ZipArchive;

class ZipReportWriter
{
    public function create(string $label, array $files): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'sfp_zip_');

        $zip = new ZipArchive;
        $zip->open($tmpPath, ZipArchive::CREATE);

        foreach ($files as $filename => $content) {
            $zip->addFromString($filename, $content);
        }

        $zip->addFromString('README.txt', $this->readmeContent($label, count($files)));

        $zip->close();

        $content = file_get_contents($tmpPath);
        unlink($tmpPath);

        return $content;
    }

    private function readmeContent(string $label, int $fileCount): string
    {
        $generatedAt = now()->format('d F Y, H:i');

        return <<<TEXT
Official Report Bundle — {$label}
Generated: {$generatedAt}
Schools included: {$fileCount}

This archive contains PDF reports generated from the SFP (School Feeding Programme) system.
For questions, contact the system administrator.
TEXT;
    }
}
