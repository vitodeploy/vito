<?php

namespace App\Http\Controllers\API;

use App\Actions\FirewallRule\ManageRule;
use App\Http\Controllers\Controller;
use App\Http\Resources\FirewallRuleResource;
use App\Models\FirewallRule;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/projects/{project}/servers/{server}/firewall-rules')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class FirewallRuleController extends Controller
{
    #[Get('/', name: 'api.projects.servers.firewall-rules', middleware: 'ability:read')]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [FirewallRule::class, $server]);

        $this->validateRoute($project, $server);

        return FirewallRuleResource::collection($server->firewallRules()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.firewall-rules.create', middleware: 'ability:write')]
    public function create(Request $request, Project $project, Server $server): FirewallRuleResource
    {
        $this->authorize('create', [FirewallRule::class, $server]);

        $this->validateRoute($project, $server);

        $firewallRule = app(ManageRule::class)->create($server, $request->all());

        return new FirewallRuleResource($firewallRule);
    }

    #[Put('{firewallRule}', name: 'api.projects.servers.firewall-rules.edit', middleware: 'ability:write')]
    public function edit(Request $request, Project $project, Server $server, FirewallRule $firewallRule): FirewallRuleResource
    {
        $this->authorize('update', [FirewallRule::class, $firewallRule]);

        $this->validateRoute($project, $server);

        $firewallRule = app(ManageRule::class)->update($firewallRule, $request->all());

        return new FirewallRuleResource($firewallRule);
    }

    #[Get('{firewallRule}', name: 'api.projects.servers.firewall-rules.show', middleware: 'ability:read')]
    public function show(Project $project, Server $server, FirewallRule $firewallRule): FirewallRuleResource
    {
        $this->authorize('view', [$firewallRule, $server]);

        $this->validateRoute($project, $server, $firewallRule);

        return new FirewallRuleResource($firewallRule);
    }

    #[Delete('{firewallRule}', name: 'api.projects.servers.firewall-rules.delete', middleware: 'ability:write')]
    public function delete(Project $project, Server $server, FirewallRule $firewallRule): Response
    {
        $this->authorize('delete', [$firewallRule, $server]);

        $this->validateRoute($project, $server, $firewallRule);

        app(ManageRule::class)->delete($firewallRule);

        return response()->noContent();
    }

    private function validateRoute(Project $project, Server $server, ?FirewallRule $firewallRule = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($firewallRule && $firewallRule->server_id !== $server->id) {
            abort(404, 'Firewall rule not found in server');
        }
    }
}
