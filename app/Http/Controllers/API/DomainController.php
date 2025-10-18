<?php

namespace App\Http\Controllers\API;

use App\Actions\Domain\AddDomain;
use App\Actions\Domain\RemoveDomain;
use App\Http\Controllers\Controller;
use App\Http\Resources\DomainResource;
use App\Models\Domain;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/domains')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class DomainController extends Controller
{
    #[Get('/', name: 'api.projects.domains', middleware: 'ability:read')]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', [Domain::class, $project]);

        $domains = $project->domains()->with('dnsProvider')->simplePaginate(25);

        return DomainResource::collection($domains);
    }

    #[Post('/', name: 'api.projects.domains.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project): DomainResource
    {
        $this->authorize('create', [Domain::class, $project]);

        $user = user();

        $domain = app(AddDomain::class)->add($user, $project, $request->all());

        return new DomainResource($domain->load('dnsProvider'));
    }

    #[Get('{domain}', name: 'api.projects.domains.show', middleware: 'ability:read')]
    public function show(Project $project, Domain $domain): DomainResource
    {
        $this->authorize('view', $domain);

        $this->validateRoute($project, $domain);

        return new DomainResource($domain->load('dnsProvider'));
    }

    #[Delete('{domain}', name: 'api.projects.domains.destroy', middleware: 'ability:write')]
    public function destroy(Project $project, Domain $domain): JsonResponse
    {
        $this->authorize('delete', $domain);

        $this->validateRoute($project, $domain);

        app(RemoveDomain::class)->remove($domain);

        return response()->json(['message' => 'Domain removed successfully']);
    }

    private function validateRoute(Project $project, Domain $domain): void
    {
        if ($project->id !== $domain->project_id) {
            abort(404, 'Domain not found in project');
        }
    }
}
