<?php

namespace App\Http\Controllers\API;

use App\Actions\StorageProvider\CreateStorageProvider;
use App\Actions\StorageProvider\DeleteStorageProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\StorageProviderResource;
use App\Models\Project;
use App\Models\StorageProvider;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group(name: 'storage-providers')]
class StorageProviderController extends Controller
{
    #[Endpoint(title: 'list')]
    #[ResponseFromApiResource(StorageProviderResource::class, StorageProvider::class, collection: true, paginate: 25)]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', StorageProvider::class);

        $storageProviders = StorageProvider::getByProjectId($project->id)->simplePaginate(25);

        return StorageProviderResource::collection($storageProviders);
    }

    #[Endpoint(title: 'create')]
    #[BodyParam(name: 'provider', description: 'The provider (aws, linode, hetzner, digitalocean, vultr, ...)', required: true)]
    #[BodyParam(name: 'name', description: 'The name of the storage provider.', required: true)]
    #[BodyParam(name: 'token', description: 'The token if provider requires api token')]
    #[BodyParam(name: 'key', description: 'The key if provider requires key')]
    #[BodyParam(name: 'secret', description: 'The secret if provider requires key')]
    #[ResponseFromApiResource(StorageProviderResource::class, StorageProvider::class)]
    public function create(Request $request, Project $project): StorageProviderResource
    {
        $this->authorize('create', StorageProvider::class);

        $this->validate($request, CreateStorageProvider::rules($request->all()));

        $storageProvider = app(CreateStorageProvider::class)->create(auth()->user(), $project, $request->all());

        return new StorageProviderResource($storageProvider);
    }

    #[Endpoint(title: 'show')]
    #[ResponseFromApiResource(StorageProviderResource::class, StorageProvider::class)]
    public function show(Project $project, StorageProvider $storageProvider)
    {
        $this->authorize('view', $storageProvider);

        return new StorageProviderResource($storageProvider);
    }

    #[Endpoint(title: 'update')]
    #[ResponseFromApiResource(StorageProviderResource::class, StorageProvider::class)]
    public function update(Request $request, Project $project, StorageProvider $storageProvider) {}

    /**
     * @throws Exception
     */
    #[Endpoint(title: 'delete')]
    #[Response(status: 204)]
    public function delete(Project $project, StorageProvider $storageProvider)
    {
        $this->authorize('delete', $storageProvider);

        app(DeleteStorageProvider::class)->delete($storageProvider);

        return response()->noContent();
    }
}
