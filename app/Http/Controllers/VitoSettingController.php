<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

#[Prefix('settings/vito')]
#[Middleware(['auth', 'must-be-admin'])]
class VitoSettingController extends Controller
{
    /**
     * @var array<string, string>
     */
    protected array $paths = [
        'storage/database.sqlite' => 'file',
        '.env' => 'file',
        'storage/ssh-public.key' => 'file',
        'storage/ssh-private.pem' => 'file',
        'storage/app/key-pairs' => 'directory',
        'storage/app/server-logs' => 'directory',
    ];

    #[Get('/', name: 'vito-settings')]
    public function index(): Response
    {
        return Inertia::render('vito-settings/index');
    }

    /**
     * @throws Exception
     */
    #[Get('/export', name: 'vito-settings.export')]
    public function downloadExport(): BinaryFileResponse
    {
        $exportName = 'vito-backup-'.date('Y-m-d').'.zip';
        $export = $this->export($exportName);

        return response()->download($export, $exportName)->deleteFileAfterSend();
    }

    /**
     * @throws Exception
     */
    private function export(string $zipFileName): string
    {
        $zipPath = Storage::disk('tmp')->path($zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception('Could not create zip file at '.$zipPath);
        }

        foreach ($this->paths as $path => $type) {
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
     * @throws ValidationException
     * @throws Exception
     */
    #[Post('/import', name: 'vito-settings.import')]
    public function import(Request $request): RedirectResponse
    {
        if (config('app.demo')) {
            return back()->with('error', 'Import is disabled in demo mode.');
        }

        // set session driver to file
        config(['session.driver' => 'file']);

        $request->validate([
            'backup_file' => 'required|file|mimes:zip',
        ]);

        $extractPath = $this->extractBackupFile($request->file('backup_file'));
        
        try {
            $this->validateBackupStructure($extractPath);
            $this->moveBackupFiles($extractPath);
            
            Artisan::call('optimize');
            
            return redirect()->route('vito-settings')
                ->with('success', 'Settings imported successfully.');
        } finally {
            File::deleteDirectory($extractPath);
        }
    }

    /**
     * @throws ValidationException
     * @throws Exception
     */
    private function extractBackupFile($uploadedFile): string
    {
        $extractName = 'vito-backup-import-'.time();
        $extractPath = Storage::disk('tmp')->path($extractName);

        File::makeDirectory($extractPath, 0755, true);

        $zip = new ZipArchive;
        if ($zip->open($uploadedFile->getPathname()) !== true) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file is not a valid zip archive.'
            ]);
        }

        $zip->extractTo($extractPath);
        $zip->close();

        return $extractPath;
    }

    /**
     * @throws ValidationException
     */
    private function validateBackupStructure(string $extractPath): void
    {
        $dbPaths = [
            $extractPath . '/database.sqlite',
            $extractPath . '/storage/database.sqlite'
        ];

        $dbPath = null;
        foreach ($dbPaths as $path) {
            if (file_exists($path)) {
                $dbPath = $path;
                break;
            }
        }

        if (!$dbPath) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded backup file is not valid. Database file not found.'
            ]);
        }
    }

    private function moveBackupFiles(string $extractPath): void
    {
        $fileMap = [
            'database' => [
                'sources' => ['database.sqlite', 'storage/database.sqlite'],
                'destination' => storage_path('database.sqlite')
            ],
            'env' => [
                'sources' => ['.env'],
                'destination' => base_path('.env')
            ],
            'ssh_public' => [
                'sources' => ['ssh-public.key'],
                'destination' => storage_path('ssh-public.key')
            ],
            'ssh_private' => [
                'sources' => ['ssh-private.pem'],
                'destination' => storage_path('ssh-private.pem')
            ],
        ];

        $directoryMap = [
            'key_pairs' => [
                'sources' => ['key-pairs'],
                'destination' => storage_path('app/key-pairs')
            ],
            'server_logs' => [
                'sources' => ['server-logs'],
                'destination' => storage_path('app/server-logs')
            ],
        ];

        foreach ($fileMap as $config) {
            foreach ($config['sources'] as $sourcePath) {
                $fullPath = $extractPath . '/' . $sourcePath;
                if (File::exists($fullPath)) {
                    File::move($fullPath, $config['destination']);
                    break;
                }
            }
        }

        foreach ($directoryMap as $config) {
            foreach ($config['sources'] as $sourcePath) {
                $fullPath = $extractPath . '/' . $sourcePath;
                if (File::exists($fullPath)) {
                    move_directory($fullPath, $config['destination']);
                    break;
                }
            }
        }
    }

    private function addDirectoryToZip(ZipArchive $zip, string $path, string $zipPath): void
    {
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $relativePath = $zipPath.'/'.$file->getRelativePathname();
            $zip->addFile($file->getRealPath(), $relativePath);
        }
    }
}
