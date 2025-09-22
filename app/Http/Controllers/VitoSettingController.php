<?php

namespace App\Http\Controllers;

use App\Actions\VitoBackup\BackupOperations;
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
    use BackupOperations;

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

        // Import database based on current driver
        $this->importDatabase($extractPath);

        // Replace other files
        if (File::exists($extractPath.'/.env')) {
            File::move($extractPath.'/.env', base_path('.env'));
        }
        File::move($extractPath.'/ssh-public.key', storage_path('ssh-public.key'));
        File::move($extractPath.'/ssh-private.pem', storage_path('ssh-private.pem'));
        if (File::exists($extractPath.'/key-pairs')) {
            move_directory($extractPath.'/key-pairs', storage_path('app/key-pairs'));
        }
        if (File::exists($extractPath.'/server-logs')) {
            move_directory($extractPath.'/server-logs', storage_path('app/server-logs'));
        }

        Artisan::call('optimize');

        return redirect()->route('vito-settings')
            ->with('success', 'Settings imported successfully.');
    }
}
