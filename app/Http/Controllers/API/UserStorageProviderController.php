<?php

namespace App\Http\Controllers\API;

use App\Actions\StorageProvider\CreateStorageProvider;
use App\Actions\StorageProvider\DeleteStorageProvider;
use App\Actions\StorageProvider\EditStorageProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\StorageProviderResource;
use App\Models\StorageProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/storage-providers')]
#[Middleware(['auth:sanctum'])]
class UserStorageProviderController extends Controller
{
    #[Get('/', name: 'api.user.storage-providers', middleware: 'ability:read')]
    public function index(): ResourceCollection
    {
        $this->authorize('viewAny', StorageProvider::class);

        $storageProviders = user()->storageProviders()->simplePaginate(25);

        return StorageProviderResource::collection($storageProviders);
    }

    #[Post('/', name: 'api.user.storage-providers.create', middleware: 'ability:write')]
    public function create(Request $request): StorageProviderResource
    {
        $this->authorize('create', StorageProvider::class);

        $user = user();
        $storageProvider = app(CreateStorageProvider::class)->create($user, $request->all());

        return new StorageProviderResource($storageProvider);
    }

    #[Get('{storageProvider}', name: 'api.user.storage-providers.show', middleware: 'ability:read')]
    public function show(StorageProvider $storageProvider): StorageProviderResource
    {
        $this->authorize('view', $storageProvider);

        // Ensure the storage provider belongs to the authenticated user
        if ($storageProvider->user_id !== user()->id) {
            abort(404, 'Storage provider not found');
        }

        return new StorageProviderResource($storageProvider);
    }

    #[Put('{storageProvider}', name: 'api.user.storage-providers.update', middleware: 'ability:write')]
    public function update(Request $request, StorageProvider $storageProvider): StorageProviderResource
    {
        $this->authorize('update', $storageProvider);

        // Ensure the storage provider belongs to the authenticated user
        if ($storageProvider->user_id !== user()->id) {
            abort(404, 'Storage provider not found');
        }

        $storageProvider = app(EditStorageProvider::class)->edit($storageProvider, $request->all());

        return new StorageProviderResource($storageProvider);
    }

    #[Delete('{storageProvider}', name: 'api.user.storage-providers.delete', middleware: 'ability:write')]
    public function delete(StorageProvider $storageProvider): Response
    {
        $this->authorize('delete', $storageProvider);

        // Ensure the storage provider belongs to the authenticated user
        if ($storageProvider->user_id !== user()->id) {
            abort(404, 'Storage provider not found');
        }

        app(DeleteStorageProvider::class)->delete($storageProvider);

        return response()->noContent();
    }
}
