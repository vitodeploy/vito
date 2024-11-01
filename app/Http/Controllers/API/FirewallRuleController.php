<?php

namespace App\Http\Controllers\API;

use App\Actions\FirewallRule\CreateRule;
use App\Actions\FirewallRule\DeleteRule;
use App\Http\Controllers\Controller;
use App\Http\Resources\FirewallRuleResource;
use App\Models\FirewallRule;
use App\Models\Project;
use App\Models\Server;
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

#[Prefix('api/projects/{project}/servers/{server}/firewall-rules')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
#[Group(name: 'firewall-rules')]
class FirewallRuleController extends Controller
{
    #[Get('/', name: 'api.projects.servers.firewall-rules', middleware: 'ability:read')]
    #[Endpoint(title: 'list', description: 'Get all firewall rules.')]
    #[ResponseFromApiResource(FirewallRuleResource::class, FirewallRule::class, collection: true, paginate: 25)]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [FirewallRule::class, $server]);

        $this->validateRoute($project, $server);

        return FirewallRuleResource::collection($server->firewallRules()->simplePaginate(25));
    }

    #[Post('/', name: 'api.projects.servers.firewall-rules.create', middleware: 'ability:write')]
    #[Endpoint(title: 'create', description: 'Create a new firewall rule.')]
    #[BodyParam(name: 'type', required: true, enum: ['allow', 'deny'])]
    #[BodyParam(name: 'protocol', required: true, enum: ['tcp', 'udp'])]
    #[BodyParam(name: 'port', required: true)]
    #[BodyParam(name: 'source', required: true)]
    #[BodyParam(name: 'mask', description: 'Mask for source IP.', example: '0')]
    #[ResponseFromApiResource(FirewallRuleResource::class, FirewallRule::class)]
    public function create(Request $request, Project $project, Server $server): FirewallRuleResource
    {
        $this->authorize('create', [FirewallRule::class, $server]);

        $this->validateRoute($project, $server);

        $this->validate($request, CreateRule::rules());

        $firewallRule = app(CreateRule::class)->create($server, $request->all());

        return new FirewallRuleResource($firewallRule);
    }

    #[Get('{firewallRule}', name: 'api.projects.servers.firewall-rules.show', middleware: 'ability:read')]
    #[Endpoint(title: 'show', description: 'Get a firewall rule by ID.')]
    #[ResponseFromApiResource(FirewallRuleResource::class, FirewallRule::class)]
    public function show(Project $project, Server $server, FirewallRule $firewallRule): FirewallRuleResource
    {
        $this->authorize('view', [$firewallRule, $server]);

        $this->validateRoute($project, $server, $firewallRule);

        return new FirewallRuleResource($firewallRule);
    }

    #[Delete('{firewallRule}', name: 'api.projects.servers.firewall-rules.delete', middleware: 'ability:write')]
    #[Endpoint(title: 'delete', description: 'Delete firewall rule.')]
    #[Response(status: 204)]
    public function delete(Project $project, Server $server, FirewallRule $firewallRule)
    {
        $this->authorize('delete', [$firewallRule, $server]);

        $this->validateRoute($project, $server, $firewallRule);

        app(DeleteRule::class)->delete($server, $firewallRule);

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
