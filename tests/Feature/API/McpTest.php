<?php

use App\Enums\UserRole;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * @param  array<int, string>  $abilities
 * @param  array<string, mixed>  $params
 */
function vitoPestFeatureApiMcpTestRequest(User $user, string $method, array $params = [], array $abilities = ['read', 'write']): TestResponse
{
    return test()
        ->withToken($user->createToken('mcp', $abilities)->plainTextToken)
        ->postJson(route('api.mcp'), ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
}

/**
 * @param  array<string, mixed>  $arguments
 * @param  array<int, string>  $abilities
 * @return array{content: array<int, array{type: string, text: string}>, isError: bool}
 */
function vitoPestFeatureApiMcpTestCallTool(User $user, string $name, array $arguments = [], array $abilities = ['read', 'write']): array
{
    return vitoPestFeatureApiMcpTestRequest($user, 'tools/call', ['name' => $name, 'arguments' => $arguments], $abilities)
        ->assertOk()
        ->json('result');
}

test('rejects unauthenticated requests', function () {
    $this->postJson(route('api.mcp'), ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], ['Accept' => 'text/event-stream, application/json'])
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate');
});

test('throttles mcp messages separately from the api routes tool calls replay through', function () {
    expect(app('router')->gatherRouteMiddleware(Route::getRoutes()->getByName('api.mcp')))
        ->toContain(ThrottleRequests::class.':mcp')
        ->not->toContain(ThrottleRequests::class.':api');
});

test('initializes as the vito server', function () {
    vitoPestFeatureApiMcpTestRequest($this->user, 'initialize', [
        'protocolVersion' => '2025-06-18',
        'capabilities' => [],
        'clientInfo' => ['name' => 'test', 'version' => '1.0'],
    ])
        ->assertOk()
        ->assertJsonPath('result.serverInfo.name', 'Vito')
        ->assertJsonPath('result.serverInfo.version', config('app.version'));
});

test('exposes every token authenticated api route as a tool', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => str_starts_with((string) $route->getName(), 'api.')
            && $route->getName() !== 'api.mcp'
            && in_array('auth:sanctum', $route->gatherMiddleware(), true));

    $tools = collect(vitoPestFeatureApiMcpTestRequest($this->user, 'tools/list')->assertOk()->json('result.tools'))->keyBy('name');

    expect($tools)->toHaveCount($routes->count())
        ->and($tools->keys()->every(fn (string $name): bool => preg_match('/^[a-z0-9_]{1,64}$/', $name) === 1))->toBeTrue()
        ->and($tools->has('mcp'))->toBeFalse()
        ->and(json_encode($tools->pluck('inputSchema')))->not->toContain('"example"')->not->toContain('"nullable"');

    expect($tools['projects_servers_databases_create'])
        ->description->toContain('POST /api/projects/{project}/servers/{server}/databases')
        ->annotations->toMatchArray(['readOnlyHint' => false])->not->toHaveKey('destructiveHint')
        ->inputSchema->required->toBe(['project', 'server', 'name', 'charset', 'collation']);

    expect($tools['projects_servers_databases']['annotations'])->toMatchArray(['readOnlyHint' => true])
        ->and($tools['projects_servers_databases']['inputSchema']['properties'])->toHaveKey('page')
        ->and($tools['projects_servers_workers_create']['inputSchema']['required'])->toContain('command');
});

test('hides write tools from read only tokens', function () {
    $tools = collect(vitoPestFeatureApiMcpTestRequest($this->user, 'tools/list', abilities: ['read'])->assertOk()->json('result.tools'));

    expect($tools->pluck('name'))->toContain('projects_servers_databases')
        ->not->toContain('projects_servers_databases_create')
        ->and($tools->every(fn (array $tool): bool => $tool['annotations']['readOnlyHint']))->toBeTrue();

    vitoPestFeatureApiMcpTestRequest($this->user, 'tools/call', [
        'name' => 'projects_servers_databases_create',
        'arguments' => ['project' => $this->server->project_id, 'server' => $this->server->id],
    ], ['read'])->assertJsonPath('error.code', -32602);
});

test('lists resources through the api', function () {
    /** @var Database $database */
    $database = Database::factory()->create(['server_id' => $this->server]);

    $result = vitoPestFeatureApiMcpTestCallTool($this->user, 'projects_servers_databases', [
        'project' => $this->server->project_id,
        'server' => $this->server->id,
    ]);

    expect($result['isError'])->toBeFalse()
        ->and(json_decode($result['content'][0]['text'], true)['data'][0]['name'])->toBe($database->name);
});

test('creates resources through the api', function () {
    SSH::fake();

    $result = vitoPestFeatureApiMcpTestCallTool($this->user, 'projects_servers_databases_create', [
        'project' => $this->server->project_id,
        'server' => $this->server->id,
        'name' => 'mcp_database',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ]);

    expect($result['isError'])->toBeFalse();
    $this->assertDatabaseHas('databases', ['server_id' => $this->server->id, 'name' => 'mcp_database']);
});

test('returns delete responses without content as success', function () {
    SSH::fake();

    /** @var Database $database */
    $database = Database::factory()->create(['server_id' => $this->server]);

    $result = vitoPestFeatureApiMcpTestCallTool($this->user, 'projects_servers_databases_delete', [
        'project' => $this->server->project_id,
        'server' => $this->server->id,
        'database' => $database->id,
    ]);

    expect($result)->toMatchArray(['isError' => false, 'content' => [['type' => 'text', 'text' => 'Done.']]]);
});

test('returns api validation errors', function () {
    $result = vitoPestFeatureApiMcpTestCallTool($this->user, 'projects_servers_databases_create', [
        'project' => $this->server->project_id,
        'server' => $this->server->id,
    ]);

    expect($result['isError'])->toBeTrue()
        ->and($result['content'][0]['text'])->toStartWith('HTTP 422:')->toContain('charset');
});

test('enforces the token project scope', function () {
    /** @var Project $project */
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::ADMIN]);

    $result = vitoPestFeatureApiMcpTestCallTool($this->user, 'projects_servers', ['project' => $project->id], ['read', 'project:'.$this->server->project_id]);

    expect($result['isError'])->toBeTrue()
        ->and($result['content'][0]['text'])->toStartWith('HTTP 403:');
});

test('rejects path parameters that would change the target route', function () {
    $result = vitoPestFeatureApiMcpTestCallTool($this->user, 'projects_servers_show', [
        'project' => $this->server->project_id,
        'server' => $this->server->id.'/databases',
    ]);

    expect($result['isError'])->toBeTrue()
        ->and($result['content'][0]['text'])->toContain('server');
});

test('rejects optional parameters that would change the target route', function () {
    $result = vitoPestFeatureApiMcpTestCallTool($this->user, 'projects_servers_workers_create', [
        'project' => $this->server->project_id,
        'server' => $this->server->id,
        'site' => 'resync',
    ]);

    expect($result)->toMatchArray([
        'isError' => true,
        'content' => [['type' => 'text', 'text' => 'The given parameters resolve to a different endpoint.']],
    ]);
});
