<?php

namespace App\Http\Controllers;

use App\Actions\VitoBackup\CreateVitoBackup;
use App\Actions\VitoBackup\DeleteVitoBackup;
use App\Actions\VitoBackup\RunVitoBackup;
use App\Actions\VitoBackup\UpdateVitoBackup;
use App\Http\Resources\VitoBackupResource;
use App\Models\StorageProvider;
use App\Models\VitoBackup;
use App\Models\VitoBackupFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Prefix('settings/vito/backups')]
#[Middleware(['auth', 'must-be-admin', 'throttle:10,1'])]
class VitoBackupController extends Controller
{
    /**
     * Display a listing of Vito backups with their associated storage providers.
     */
    #[Get('/', name: 'vito-backups.index')]
    public function index(): Response
    {
        $vitoBackups = VitoBackup::with(['storage', 'files'])
            ->orderBy('created_at', 'desc')
            ->get();

        $storageProviders = StorageProvider::whereNull('project_id')
            ->whereIn('provider', ['s3', 'dropbox', 'ftp'])
            ->orderBy('profile')
            ->get();

        return Inertia::render('vito-settings/backups/index', [
            'vitoBackups' => VitoBackupResource::collection($vitoBackups),
            'storageProviders' => $storageProviders,
        ]);
    }

    /**
     * Store a newly created Vito backup.
     *
     * @throws ValidationException
     */
    #[Post('/', name: 'vito-backups.store')]
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'frequency' => 'required|string|in:0 * * * *,0 0 * * *,0 0 * * 0,0 0 1 * *',
            'keep_backups' => 'required|integer|min:1|max:100',
            'storage_id' => 'required|integer|exists:storage_providers,id',
        ]);

        try {
            app(CreateVitoBackup::class)->create($request->all());

            return redirect()->route('vito-backups.index')
                ->with('success', 'Vito backup created successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Update the specified Vito backup.
     *
     * @throws ValidationException
     */
    #[Put('/{vitoBackup}', name: 'vito-backups.update')]
    public function update(Request $request, VitoBackup $vitoBackup): RedirectResponse
    {
        $request->validate([
            'frequency' => 'required|string|in:0 * * * *,0 0 * * *,0 0 * * 0,0 0 1 * *',
            'keep_backups' => 'required|integer|min:1|max:100',
        ]);

        try {
            app(UpdateVitoBackup::class)->update($vitoBackup, $request->all());

            return redirect()->route('vito-backups.index')
                ->with('success', 'Vito backup updated successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Remove the specified Vito backup from storage.
     */
    #[Delete('/{vitoBackup}', name: 'vito-backups.destroy')]
    public function destroy(VitoBackup $vitoBackup): RedirectResponse
    {
        app(DeleteVitoBackup::class)->delete($vitoBackup);

        return redirect()->route('vito-backups.index')
            ->with('success', 'Vito backup deleted successfully.');
    }

    /**
     * Manually trigger a Vito backup.
     */
    #[Post('/{vitoBackup}/run', name: 'vito-backups.run')]
    public function run(VitoBackup $vitoBackup): RedirectResponse
    {
        $this->authorize('update', $vitoBackup);

        try {
            app(RunVitoBackup::class)->run($vitoBackup);

            return redirect()->route('vito-backups.index')
                ->with('success', 'Vito backup started successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('vito-backups.index')
                ->with('error', 'Invalid backup configuration: '.$e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('vito-backups.index')
                ->with('error', 'Database error during backup: '.$e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('vito-backups.index')
                ->with('error', 'Failed to start backup: '.$e->getMessage());
        }
    }

    /**
     * Download a backup file from storage provider.
     *
     * @throws \Exception
     */
    #[Get('/files/{vitoBackupFile}/download', name: 'vito-backup-files.download')]
    public function downloadFile(VitoBackupFile $vitoBackupFile): BinaryFileResponse
    {
        $this->authorize('view', $vitoBackupFile);

        // Add null checks for relationships
        $vitoBackup = $vitoBackupFile->vitoBackup;
        if (! $vitoBackup) {
            abort(404, 'Backup not found');
        }

        $storageModel = $vitoBackup->storage;
        if (! $storageModel) {
            abort(404, 'Storage provider not found');
        }

        if (! $vitoBackupFile->path) {
            abort(404, 'Backup file not available');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'vito-backup-');

        try {
            // Download based on storage provider type
            switch ($storageModel->provider) {
                case 's3':
                    $this->downloadFromS3($storageModel, $vitoBackupFile->path, $tempFile);
                    break;
                case 'dropbox':
                    $this->downloadFromDropbox($storageModel, $vitoBackupFile->path, $tempFile);
                    break;
                case 'ftp':
                    $this->downloadFromFTP($storageModel, $vitoBackupFile->path, $tempFile);
                    break;
                default:
                    throw new \InvalidArgumentException('Unsupported storage provider: '.$storageModel->provider);
            }

            return response()->download($tempFile, $vitoBackupFile->name)->deleteFileAfterSend();

        } catch (\Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw $e;
        }
    }

    /**
     * Download file from S3 storage provider.
     *
     * @param  mixed  $storageModel
     *
     * @throws \Exception
     */
    private function downloadFromS3($storageModel, string $path, string $tempFile): void
    {
        $s3Provider = $storageModel->provider();
        $s3Provider->buildClientConfig(); // Ensure client config is built
        $s3Client = $s3Provider->getClient();

        $bucket = $storageModel->credentials['bucket'];
        $fullPath = rtrim($storageModel->credentials['path'] ?? '', '/');
        $fullPath = $fullPath ? $fullPath.'/'.$path : $path;

        $result = $s3Client->getObject([
            'Bucket' => $bucket,
            'Key' => $fullPath,
        ]);

        file_put_contents($tempFile, $result['Body']);
    }

    /**
     * Download file from Dropbox storage provider.
     *
     * @param  mixed  $storageModel
     *
     * @throws \Exception
     */
    private function downloadFromDropbox($storageModel, string $path, string $tempFile): void
    {
        $token = $storageModel->credentials['token'];
        $fullPath = '/'.ltrim($path, '/');

        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->withHeaders([
                'Dropbox-API-Arg' => json_encode(['path' => $fullPath]),
            ])
            ->post('https://content.dropboxapi.com/2/files/download');

        if (! $response->successful()) {
            throw new \Exception('Failed to download from Dropbox: '.$response->body());
        }

        file_put_contents($tempFile, $response->body());
    }

    /**
     * Download file from FTP storage provider.
     *
     * @param  mixed  $storageModel
     *
     * @throws \Exception
     */
    private function downloadFromFTP($storageModel, string $path, string $tempFile): void
    {
        $credentials = $storageModel->credentials;

        $connection = \App\Facades\FTP::connect(
            $credentials['host'],
            (int) $credentials['port'],
            (bool) $credentials['ssl']
        );

        if (! $connection) {
            throw new \Exception('Failed to connect to FTP server');
        }

        $login = \App\Facades\FTP::login(
            $credentials['username'],
            $credentials['password'],
            $connection
        );

        if (! $login) {
            \App\Facades\FTP::close($connection);
            throw new \Exception('Failed to login to FTP server');
        }

        $fullPath = rtrim($credentials['path'], '/').'/'.$path;
        $downloaded = \App\Facades\FTP::get($connection, $tempFile, $fullPath);

        \App\Facades\FTP::close($connection);

        if (! $downloaded) {
            throw new \Exception('Failed to download file from FTP server');
        }
    }

    /**
     * Remove the specified backup file from storage and database.
     */
    #[Delete('/files/{vitoBackupFile}', name: 'vito-backup-files.destroy')]
    public function destroyFile(VitoBackupFile $vitoBackupFile): RedirectResponse
    {
        $this->authorize('delete', $vitoBackupFile);

        try {
            // Delete from storage provider
            $this->deleteFromStorage($vitoBackupFile);

            // Delete from database
            $vitoBackupFile->delete();

            return redirect()->route('vito-backups.index')
                ->with('success', 'Backup file deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->route('vito-backups.index')
                ->with('error', 'Failed to delete backup file: '.$e->getMessage());
        }
    }

    /**
     * Delete backup file from storage provider.
     *
     * @throws \Exception
     */
    private function deleteFromStorage(VitoBackupFile $vitoBackupFile): void
    {
        $storageModel = $vitoBackupFile->vitoBackup->storage;

        switch ($storageModel->provider) {
            case 's3':
                $this->deleteFromS3($storageModel, $vitoBackupFile->path);
                break;
            case 'dropbox':
                $this->deleteFromDropbox($storageModel, $vitoBackupFile->path);
                break;
            case 'ftp':
                $this->deleteFromFTP($storageModel, $vitoBackupFile->path);
                break;
            default:
                throw new \Exception('Unsupported storage provider: '.$storageModel->provider);
        }
    }

    /**
     * Delete file from S3 storage provider.
     *
     * @param  mixed  $storageModel
     *
     * @throws \Exception
     */
    private function deleteFromS3($storageModel, string $path): void
    {
        $s3Provider = $storageModel->provider();
        $s3Provider->buildClientConfig(); // Ensure client config is built
        $s3Client = $s3Provider->getClient();

        $bucket = $storageModel->credentials['bucket'];
        $fullPath = rtrim($storageModel->credentials['path'] ?? '', '/');
        $fullPath = $fullPath ? $fullPath.'/'.$path : $path;

        $s3Client->deleteObject([
            'Bucket' => $bucket,
            'Key' => $fullPath,
        ]);
    }

    /**
     * Delete file from Dropbox storage provider.
     *
     * @param  mixed  $storageModel
     *
     * @throws \Exception
     */
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
            throw new \Exception('Failed to delete from Dropbox: '.$response->body());
        }
    }

    /**
     * Delete file from FTP storage provider.
     *
     * @param  mixed  $storageModel
     *
     * @throws \Exception
     */
    private function deleteFromFTP($storageModel, string $path): void
    {
        $credentials = $storageModel->credentials;

        $connection = \App\Facades\FTP::connect(
            $credentials['host'],
            (int) $credentials['port'],
            (bool) $credentials['ssl']
        );

        if (! $connection) {
            throw new \Exception('Failed to connect to FTP server');
        }

        $login = \App\Facades\FTP::login(
            $credentials['username'],
            $credentials['password'],
            $connection
        );

        if (! $login) {
            \App\Facades\FTP::close($connection);
            throw new \Exception('Failed to login to FTP server');
        }

        $fullPath = rtrim($credentials['path'], '/').'/'.$path;
        $deleted = \App\Facades\FTP::delete($connection, $fullPath);

        \App\Facades\FTP::close($connection);

        if (! $deleted) {
            throw new \Exception('Failed to delete file from FTP server');
        }
    }
}
