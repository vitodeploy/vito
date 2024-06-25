<?php

namespace App\Http\Controllers\Settings;

use App\Actions\StorageProvider\CreateStorageProvider;
use App\Actions\StorageProvider\DeleteStorageProvider;
use App\Actions\StorageProvider\EditStorageProvider;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\StorageProvider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StorageProviderController extends Controller
{
    public function index(Request $request): View
    {
        $data = [
            'providers' => StorageProvider::getByProjectId(auth()->user()->current_project_id)->get(),
        ];

        if ($request->has('edit')) {
            $data['editProvider'] = StorageProvider::find($request->input('edit'));
        }

        return view('settings.storage-providers.index', $data);
    }

    public function connect(Request $request): HtmxResponse
    {
        app(CreateStorageProvider::class)->create(
            $request->user(),
            $request->input()
        );

        Toast::success('Storage provider connected.');

        return htmx()->redirect(route('settings.storage-providers'));
    }

    public function update(StorageProvider $storageProvider, Request $request): HtmxResponse
    {
        app(EditStorageProvider::class)->edit(
            $storageProvider,
            $request->user(),
            $request->input(),
        );

        Toast::success('Provider updated.');

        return htmx()->redirect(route('settings.storage-providers'));
    }

    public function delete(StorageProvider $storageProvider): RedirectResponse
    {
        try {
            app(DeleteStorageProvider::class)->delete($storageProvider);
        } catch (\Exception $e) {
            Toast::error($e->getMessage());

            return back();
        }

        Toast::success('Storage provider deleted.');

        return back();
    }
}
