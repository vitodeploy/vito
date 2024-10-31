<?php

namespace App\Http\Controllers\API;

use App\Actions\StorageProvider\CreateStorageProvider;
use App\Actions\StorageProvider\DeleteStorageProvider;
use App\Actions\StorageProvider\EditStorageProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\StorageProviderResource;
use App\Models\Project;
use App\Models\StorageProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/projects/{project}/storage-providers')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'storage-providers')]
class StorageProviderController extends Controller
{
    #[Get('/', name: 'api.projects.storage-providers', middleware: 'ability:read')]
    #[Endpoint(title: 'list')]
    #[ResponseFromApiResource(StorageProviderResource::class, StorageProvider::class, collection: true, paginate: 25)]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', StorageProvider::class);

        $storageProviders = StorageProvider::getByProjectId($project->id)->simplePaginate(25);

        return StorageProviderResource::collection($storageProviders);
    }

    #[Post('/', name: 'api.projects.storage-providers.create', middleware: 'ability:write')]
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

    #[Get('{storageProvider}', name: 'api.projects.storage-providers.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show')]
    #[ResponseFromApiResource(StorageProviderResource::class, StorageProvider::class)]
    public function show(Project $project, StorageProvider $storageProvider)
    {
        $this->authorize('view', $storageProvider);

        $this->validateRoute($project, $storageProvider);

        return new StorageProviderResource($storageProvider);
    }

    #[Put('{storageProvider}', name: 'api.projects.storage-providers.update', middleware: 'ability:write')]
    #[Endpoint(title: 'update')]
    #[BodyParam(name: 'name', description: 'The name of the storage provider.', required: true)]
    #[BodyParam(name: 'global', description: 'Accessible in all projects', enum: [true, false])]
    #[ResponseFromApiResource(StorageProviderResource::class, StorageProvider::class)]
    public function update(Request $request, Project $project, StorageProvider $storageProvider)
    {
        $this->authorize('update', $storageProvider);

        $this->validateRoute($project, $storageProvider);

        $this->validate($request, EditStorageProvider::rules());

        $storageProvider = app(EditStorageProvider::class)->edit($storageProvider, $project, $request->all());

        return new StorageProviderResource($storageProvider);
    }

    #[Delete('{storageProvider}', name: 'api.projects.storage-providers.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete')]
    #[Response(status: 204)]
    public function delete(Project $project, StorageProvider $storageProvider)
    {
        $this->authorize('delete', $storageProvider);

        $this->validateRoute($project, $storageProvider);

        app(DeleteStorageProvider::class)->delete($storageProvider);

        return response()->noContent();
    }

    private function validateRoute(Project $project, StorageProvider $storageProvider): void
    {
        if (! $storageProvider->project_id) {
            return;
        }

        if ($project->id !== $storageProvider->project_id) {
            abort(404, 'Storage provider not found in project');
        }
    }
}
