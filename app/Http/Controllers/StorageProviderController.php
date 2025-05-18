<?php

namespace App\Http\Controllers;

use App\Actions\StorageProvider\CreateStorageProvider;
use App\Actions\StorageProvider\DeleteStorageProvider;
use App\Actions\StorageProvider\EditStorageProvider;
use App\Http\Resources\StorageProviderResource;
use App\Models\StorageProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/storage-providers')]
#[Middleware(['auth'])]
class StorageProviderController extends Controller
{
    #[Get('/', name: 'storage-providers')]
    public function index(): Response
    {
        $this->authorize('viewAny', StorageProvider::class);

        return Inertia::render('storage-providers/index', [
            'storageProviders' => StorageProviderResource::collection(StorageProvider::getByProjectId(user()->current_project_id)->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Get('/json', name: 'storage-providers.json')]
    public function json(): ResourceCollection
    {
        $this->authorize('viewAny', StorageProvider::class);

        return StorageProviderResource::collection(StorageProvider::getByProjectId(user()->current_project_id)->get());
    }

    #[Post('/', name: 'storage-providers.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StorageProvider::class);

        app(CreateStorageProvider::class)->create(user(), user()->currentProject, $request->all());

        return back()->with('success', 'Storage provider created.');
    }

    #[Patch('/{storageProvider}', name: 'storage-providers.update')]
    public function update(Request $request, StorageProvider $storageProvider): RedirectResponse
    {
        $this->authorize('update', $storageProvider);

        app(EditStorageProvider::class)->edit($storageProvider, user()->currentProject, $request->all());

        return back()->with('success', 'Storage provider updated.');
    }

    #[Delete('{storageProvider}', name: 'storage-providers.destroy')]
    public function destroy(StorageProvider $storageProvider): RedirectResponse
    {
        $this->authorize('delete', $storageProvider);

        app(DeleteStorageProvider::class)->delete($storageProvider);

        return to_route('storage-providers')->with('success', 'Storage provider deleted.');
    }
}
