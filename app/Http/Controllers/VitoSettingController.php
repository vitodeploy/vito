<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        // set session driver to file
        config(['session.driver' => 'file']);

        $request->validate([
            'backup_file' => 'required|file|mimes:zip',
        ]);

        $uploadedFile = $request->file('backup_file');
        $extractName = 'vito-backup-import-'.time();
        $extractPath = Storage::disk('tmp')->path($extractName);

        // Create extraction directory
        File::makeDirectory($extractPath, 0755, true);

        $zip = new ZipArchive;
        if ($zip->open($uploadedFile->getPathname()) !== true) {
            throw ValidationException::withMessages(['file' => 'The uploaded file is not a valid zip archive.']);
        }

        // Extract files
        $zip->extractTo($extractPath);
        $zip->close();

        // Replace files
        File::move($extractPath.'/database.sqlite', storage_path('database.sqlite'));
        File::copy($extractPath.'/.env', base_path('.env'));
        File::move($extractPath.'/ssh-public.key', storage_path('ssh-public.key'));
        File::move($extractPath.'/ssh-private.pem', storage_path('ssh-private.pem'));
        File::moveDirectory($extractPath.'/key-pairs', storage_path('app/key-pairs'));
        File::moveDirectory($extractPath.'/server-logs', storage_path('app/server-logs'));

        return redirect()->route('vito-settings')
            ->with('success', 'Settings imported successfully.');
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
