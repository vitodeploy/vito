<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

#[Prefix('admin/vito')]
#[Middleware(['auth', 'must-be-admin'])]
class VitoSettingController extends Controller
{
    /**
     * @var array<string, string>
     */
    protected array $paths = [
        'storage/database.sqlite' => 'file',
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
        abort_if((bool) config('app.demo'), 403, 'Export is disabled in demo mode.');

        $workspace = $this->makeWorkspace();
        app()->terminating(fn () => File::deleteDirectory($workspace));

        $exportName = 'vito-backup-'.date('Y-m-d').'.zip';

        return response()->download($this->export($workspace.'/'.$exportName), $exportName);
    }

    /**
     * @throws Exception
     */
    private function export(string $zipPath): string
    {
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

        if (File::exists(app()->environmentFilePath())) {
            $zip->addFile(app()->environmentFilePath(), '.env');
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

        $uploadedFile = $request->file('backup_file');

        $zip = new ZipArchive;
        if ($zip->open($uploadedFile->getPathname()) !== true) {
            throw ValidationException::withMessages(['file' => 'The uploaded file is not a valid zip archive.']);
        }

        $extractPath = $this->makeWorkspace();
        $umask = umask(0077);

        try {
            $zip->extractTo($extractPath);
            $zip->close();

            File::move($extractPath.'/database.sqlite', storage_path('database.sqlite'));
            if (File::exists($extractPath.'/.env')) {
                File::move($extractPath.'/.env', app()->environmentFilePath());
            }
            File::move($extractPath.'/ssh-public.key', storage_path('ssh-public.key'));
            File::move($extractPath.'/ssh-private.pem', storage_path('ssh-private.pem'));
            if (File::exists($extractPath.'/key-pairs')) {
                move_directory($extractPath.'/key-pairs', storage_path('app/key-pairs'));
            }
            if (File::exists($extractPath.'/server-logs')) {
                move_directory($extractPath.'/server-logs', storage_path('app/server-logs'));
            }

            $this->restrictSecretPermissions();
        } finally {
            umask($umask);
            File::deleteDirectory($extractPath);
        }

        Artisan::call('optimize');

        return redirect()->route('vito-settings')
            ->with('success', 'Settings imported successfully.');
    }

    private function makeWorkspace(): string
    {
        $path = Storage::disk('tmp')->path('vito-'.Str::random(40));
        File::makeDirectory($path, 0700);

        return $path;
    }

    private function restrictSecretPermissions(): void
    {
        foreach ([storage_path('app/key-pairs'), storage_path('app/server-logs')] as $directory) {
            if (! File::isDirectory($directory)) {
                continue;
            }

            chmod($directory, 0700);
            foreach (File::directories($directory) as $child) {
                chmod($child, 0700);
            }
            foreach (File::allFiles($directory, true) as $file) {
                chmod($file->getPathname(), 0600);
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
