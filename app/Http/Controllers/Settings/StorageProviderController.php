<?php

namespace App\Http\Controllers\Settings;

use App\Actions\StorageProvider\CreateStorageProvider;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Http\Controllers\Controller;
use App\Models\StorageProvider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StorageProviderController extends Controller
{
    public function index(): View
    {
        return view('settings.storage-providers.index', [
            'providers' => auth()->user()->storageProviders,
        ]);
    }

    public function connect(Request $request): HtmxResponse
    {
        app(CreateStorageProvider::class)->create(
            $request->user(),
            $request->input()
        );

        Toast::success('Storage provider connected.');

        return htmx()->redirect(route('storage-providers'));
    }

    /**
     * @TODO Update servers using this provider
     */
    public function delete(int $id): RedirectResponse
    {
        $storageProvider = StorageProvider::query()->findOrFail($id);

        $storageProvider->delete();

        Toast::success('Storage provider deleted.');

        return back();
    }
}
