<?php

namespace App\Actions\VitoBackup;

use Exception;

trait StorageOperations
{
    private function uploadToStorage($vitoBackup, string $zipPath, $backupFile): void
    {
        $storageModel = $vitoBackup->storage;
        $storageProvider = $storageModel->provider();
        $storagePath = 'vito-backups/'.$backupFile->name;

        try {
            $fileSize = filesize($zipPath);
            $fileContents = file_get_contents($zipPath);

            // Upload based on storage provider type
            switch ($storageModel->provider) {
                case 's3':
                    $this->uploadToS3($storageModel, $fileContents, $storagePath);
                    break;
                case 'dropbox':
                    $this->uploadToDropbox($storageModel, $fileContents, $storagePath);
                    break;
                case 'ftp':
                    $this->uploadToFTP($storageModel, $zipPath, $storagePath);
                    break;
                default:
                    throw new Exception('Unsupported storage provider: '.$storageModel->provider);
            }

            // Update backup file with actual size and path
            $backupFile->size = $fileSize;
            $backupFile->path = $storagePath;
            $backupFile->status = \App\Enums\VitoBackupFileStatus::CREATED;
            $backupFile->save();

        } catch (Exception $e) {
            $backupFile->status = \App\Enums\VitoBackupFileStatus::FAILED;
            $backupFile->save();
            throw $e;
        } finally {
            // Clean up temporary file
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }
    }

    private function uploadToS3($storageModel, string $fileContents, string $storagePath): void
    {
        $s3Provider = $storageModel->provider();
        $s3Provider->buildClientConfig(); // Ensure client config is built
        $s3Client = $s3Provider->getClient();

        $bucket = $storageModel->credentials['bucket'];
        $path = $this->sanitizePath($storageModel->credentials['path'] ?? '');
        $fullPath = $path ? $path.'/'.$storagePath : $storagePath;

        $s3Client->putObject([
            'Bucket' => $bucket,
            'Key' => $fullPath,
            'Body' => $fileContents,
        ]);
    }

    private function uploadToDropbox($storageModel, string $fileContents, string $storagePath): void
    {
        $token = $storageModel->credentials['token'];
        $path = '/'.ltrim($storagePath, '/');

        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/octet-stream',
                'Dropbox-API-Arg' => json_encode(['path' => $path, 'mode' => 'overwrite']),
            ])
            ->withBody($fileContents, 'application/octet-stream')
            ->post('https://content.dropboxapi.com/2/files/upload');

        if (! $response->successful()) {
            throw new Exception('Failed to upload to Dropbox: '.$response->body());
        }
    }

    private function uploadToFTP($storageModel, string $zipPath, string $storagePath): void
    {
        $credentials = $storageModel->credentials;

        $connection = \App\Facades\FTP::connect(
            $credentials['host'],
            (int) $credentials['port'],
            (bool) $credentials['ssl']
        );

        if (! $connection) {
            throw new Exception('Failed to connect to FTP server');
        }

        $login = \App\Facades\FTP::login(
            $credentials['username'],
            $credentials['password'],
            $connection
        );

        if (! $login) {
            \App\Facades\FTP::close($connection);
            throw new Exception('Failed to login to FTP server');
        }

        $path = rtrim($credentials['path'], '/').'/'.$storagePath;
        $uploaded = \App\Facades\FTP::put($connection, $path, $zipPath);

        \App\Facades\FTP::close($connection);

        if (! $uploaded) {
            throw new Exception('Failed to upload file to FTP server');
        }
    }

    private function deleteFromStorage($vitoBackup, $file): void
    {
        // Skip deletion if file path is null (file was never successfully uploaded)
        if ($file->path === null) {
            return;
        }

        $storageModel = $vitoBackup->storage;

        switch ($storageModel->provider) {
            case 's3':
                $this->deleteFromS3($storageModel, $file->path);
                break;
            case 'dropbox':
                $this->deleteFromDropbox($storageModel, $file->path);
                break;
            case 'ftp':
                $this->deleteFromFTP($storageModel, $file->path);
                break;
            default:
                throw new Exception('Unsupported storage provider: '.$storageModel->provider);
        }
    }

    private function deleteFromS3($storageModel, string $path): void
    {
        $s3Provider = $storageModel->provider();
        $s3Provider->buildClientConfig();
        $s3Client = $s3Provider->getClient();

        $bucket = $storageModel->credentials['bucket'];
        $basePath = $this->sanitizePath($storageModel->credentials['path'] ?? '');
        $fullPath = $basePath ? $basePath.'/'.$path : $path;

        $s3Client->deleteObject([
            'Bucket' => $bucket,
            'Key' => $fullPath,
        ]);
    }

    private function deleteFromDropbox($storageModel, string $path): void
    {
        $token = $storageModel->credentials['token'];
        $fullPath = '/'.ltrim($path, '/');

        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post('https://api.dropboxapi.com/2/files/delete_v2', [
                'path' => $fullPath,
            ]);

        if (! $response->successful()) {
            throw new Exception('Failed to delete from Dropbox: '.$response->body());
        }
    }

    private function deleteFromFTP($storageModel, string $path): void
    {
        $credentials = $storageModel->credentials;

        $connection = \App\Facades\FTP::connect(
            $credentials['host'],
            (int) $credentials['port'],
            (bool) $credentials['ssl']
        );

        if (! $connection) {
            throw new Exception('Failed to connect to FTP server');
        }

        $login = \App\Facades\FTP::login(
            $credentials['username'],
            $credentials['password'],
            $connection
        );

        if (! $login) {
            \App\Facades\FTP::close($connection);
            throw new Exception('Failed to login to FTP server');
        }

        $fullPath = rtrim($credentials['path'], '/').'/'.$path;
        $deleted = \App\Facades\FTP::delete($connection, $fullPath);

        \App\Facades\FTP::close($connection);

        if (! $deleted) {
            throw new Exception('Failed to delete file from FTP server');
        }
    }

    /**
     * Sanitize path to prevent directory traversal attacks
     */
    private function sanitizePath(string $path): string
    {
        // Remove any directory traversal attempts
        $path = str_replace(['../', '..\\', '..'], '', $path);

        // Remove leading/trailing slashes and normalize
        $path = trim($path, '/\\');

        return $path;
    }
}
