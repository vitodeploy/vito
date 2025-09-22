<?php

namespace App\Actions\VitoBackup;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

trait BackupOperations
{
    /**
     * @var array<string, string>
     */
    protected array $backupPaths = [
        '.env' => 'file',
        'storage/ssh-public.key' => 'file',
        'storage/ssh-private.pem' => 'file',
        'storage/app/key-pairs' => 'directory',
        'storage/app/server-logs' => 'directory',
    ];

    /**
     * @throws Exception
     */
    protected function export(string $zipFileName): string
    {
        $zipPath = Storage::disk('tmp')->path($zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception('Could not create zip file at '.$zipPath);
        }

        // Add database export
        $this->addDatabaseExport($zip);

        // Add other files and directories
        foreach ($this->backupPaths as $path => $type) {
            $path = base_path($path);
            if ($type === 'file' && File::exists($path)) {
                $zip->addFile($path, basename($path));
            } elseif ($type === 'directory' && File::exists($path)) {
                $this->addDirectoryToZip($zip, $path, basename($path));
            }
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * @throws Exception
     */
    protected function addDatabaseExport(ZipArchive $zip): void
    {
        $driver = config('database.default');

        switch ($driver) {
            case 'sqlite':
                $this->addSqliteExport($zip);
                break;
            default:
                throw new Exception("Unsupported database driver: {$driver}");
        }
    }

    protected function addSqliteExport(ZipArchive $zip): void
    {
        $dbPath = config('database.connections.sqlite.database');
        if (File::exists($dbPath)) {
            $zip->addFile($dbPath, 'database.sqlite');
        }
    }

    /**
     * @throws Exception
     */
    protected function importDatabase(string $extractPath): void
    {
        $driver = config('database.default');

        switch ($driver) {
            case 'sqlite':
                $this->importSqlite($extractPath);
                break;
            default:
                throw new Exception("Unsupported database driver: {$driver}");
        }
    }

    protected function importSqlite(string $extractPath): void
    {
        if (File::exists($extractPath.'/database.sqlite')) {
            File::move($extractPath.'/database.sqlite', storage_path('database.sqlite'));
        }
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $path, string $zipPath): void
    {
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $relativePath = $zipPath.'/'.$file->getRelativePathname();
            $zip->addFile($file->getRealPath(), $relativePath);
        }
    }
}
