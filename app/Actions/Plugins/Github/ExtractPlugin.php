<?php

namespace App\Actions\Plugins\Github;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

final readonly class ExtractPlugin
{
    /**
     * Extract a GitHub ZIP archive containing a plugin
     *
     * @throws Exception
     */
    public function handle(string $zipPath, string $extractPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new Exception('Failed to open ZIP file: '.$zipPath);
        }

        if (File::isDirectory($extractPath)) {
            File::deleteDirectory($extractPath);
        }

        File::ensureDirectoryExists(path: dirname($extractPath));

        try {
            $rootFolder = $this->detectGitHubRootFolder($zip);
            if (! $rootFolder) {
                throw new Exception('Could not detect GitHub root folder structure');
            }

            $temp = storage_path('app'.DIRECTORY_SEPARATOR.'temp');
            $zip->extractTo($temp);

            $tempDir = $temp.DIRECTORY_SEPARATOR.$rootFolder;
            move_directory($tempDir, $extractPath);
        } finally {
            $zip->close();
        }
    }

    /**
     * @throws Exception
     */
    private function detectGitHubRootFolder(ZipArchive $zip): ?string
    {
        if ($zip->numFiles === 0) {
            return null;
        }

        $firstEntry = $zip->getNameIndex(0);

        if (Str::contains($firstEntry, '/')) {
            $rootFolder = Str::before($firstEntry, '/');

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (! Str::startsWith($entry, $rootFolder.'/') && $entry !== $rootFolder.'/') {
                    throw new Exception('Failed to detect GitHub root folder structure');
                }
            }

            return $rootFolder;
        }

        throw new Exception('Failed to detect GitHub root folder structure');
    }
}
