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
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/projects/{project}/storage-providers')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class StorageProviderController extends Controller
{
    #[Get('/', name: 'api.projects.storage-providers', middleware: 'ability:read')]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', StorageProvider::class);

        $storageProviders = StorageProvider::getByProjectId($project->id, user())->simplePaginate(25);

        return StorageProviderResource::collection($storageProviders);
    }

    #[Post('/', name: 'api.projects.storage-providers.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project): StorageProviderResource
    {
        $this->authorize('create', StorageProvider::class);

        $user = user();
        $storageProvider = app(CreateStorageProvider::class)->create($user, $project, $request->all());

        return new StorageProviderResource($storageProvider);
    }

    #[Get('{storageProvider}', name: 'api.projects.storage-providers.show', middleware: 'ability:read')]
    public function show(Project $project, StorageProvider $storageProvider): StorageProviderResource
    {
        $this->authorize('view', $storageProvider);

        $this->validateRoute($project, $storageProvider);

        return new StorageProviderResource($storageProvider);
    }

    #[Put('{storageProvider}', name: 'api.projects.storage-providers.update', middleware: 'ability:write')]
    public function update(Request $request, Project $project, StorageProvider $storageProvider): StorageProviderResource
    {
        $this->authorize('update', $storageProvider);

        $this->validateRoute($project, $storageProvider);

        $storageProvider = app(EditStorageProvider::class)->edit($storageProvider, $project, $request->all());

        return new StorageProviderResource($storageProvider);
    }

    #[Delete('{storageProvider}', name: 'api.projects.storage-providers.delete', middleware: 'ability:write')]
    public function delete(Project $project, StorageProvider $storageProvider): \Illuminate\Http\Response
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
