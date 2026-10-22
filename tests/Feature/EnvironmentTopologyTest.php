<?php

use App\Actions\Environment\GetEnvironmentTopology;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentAttachmentStatus;
use App\Enums\EnvironmentDeploymentStatus;
use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Database;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Models\EnvironmentDeployment;
use App\Models\EnvironmentDomain;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake();
    Http::preventStrayRequests();
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $this->actingAs($this->user);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'custom']);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->url = route('environments.show', [$this->application, $this->environment], false);
});

test('overview topology includes component routing and resource edges without secrets', function () {
    $app = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY, 'needs_redeploy' => true]);
    ComponentCapability::factory()->for($app)->create(['capability' => 'web', 'config' => ['start_command' => 'private-command']]);
    ComponentCapability::factory()->for($app)->create(['capability' => 'worker']);
    $lb = Component::factory()->for($this->environment)->for($this->server)->create(['type' => ComponentType::LOAD_BALANCER]);
    $lb->backends()->create(['target_component_id' => $app->id, 'port' => 10000]);
    $external = $lb->backends()->create(['ip' => '2001:db8::1', 'port' => 8080]);
    $domain = EnvironmentDomain::factory()->for($this->environment)->for($lb)->create(['status' => HostedDomainStatus::ACTIVE]);
    EnvironmentDomain::factory()->for($this->environment)->for($lb)->create(['type' => HostedDomainType::REDIRECT, 'redirect_to_domain_id' => $domain->id, 'host' => 'redirect.example.com']);
    $resourceServer = Server::factory()->create(['project_id' => $this->server->project_id]);
    $database = Database::factory()->for($resourceServer)->create();
    $attachment = EnvironmentAttachment::factory()->for($this->environment)->create([
        'server_id' => $resourceServer->id, 'attachable_id' => $database->id, 'alias' => 'REPORTS',
        'status' => EnvironmentAttachmentStatus::READY, 'credentials' => ['password' => 'attachment-secret'],
    ]);
    $redis = $this->server->services()->where('name', 'redis')->sole();
    $cache = EnvironmentAttachment::factory()->for($this->environment)->create([
        'server_id' => $this->server->id, 'attachable_type' => $redis->getMorphClass(), 'attachable_id' => $redis->id,
    ]);
    $this->environment->variables()->create(['key' => 'SECRET', 'value' => 'variable-secret', 'is_secret' => true]);
    $run = EnvironmentDeployment::factory()->for($this->environment)->create(['status' => EnvironmentDeploymentStatus::FAILED, 'variables' => [['key' => 'SECRET', 'value' => 'snapshot-secret', 'is_secret' => true]]]);

    $response = $this->get($this->url)->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('environment.needs_redeploy', true)->where('lastDeployment.id', $run->id)
        ->where('warnings', [])->has('topology.nodes', 5)->has('topology.edges', 4));
    $topology = $response->viewData('page')['props']['topology'];
    $nodes = collect($topology['nodes'])->keyBy('id');
    expect($nodes['component-'.$app->id])->toMatchArray([
        'record_id' => $app->id, 'server_id' => $this->server->id, 'server_name' => $this->server->name, 'status' => 'ready', 'status_color' => 'success',
        'capabilities' => ['web', 'worker'], 'domains' => [], 'needs_redeploy' => true, 'url' => $this->url.'?component='.$app->id,
    ])
        ->and($nodes['component-'.$lb->id])->toMatchArray(['record_id' => $lb->id, 'domains' => [$domain->host], 'url' => $this->url.'?component='.$lb->id])
        ->and($nodes['external-'.$external->id])->toMatchArray(['name' => '[2001:db8::1]:8080', 'record_id' => null, 'server_id' => null, 'server_name' => null, 'status' => 'unmonitored', 'url' => $this->url.'?component='.$lb->id])
        ->and($nodes['resource-'.$attachment->id])->toMatchArray(['name' => 'REPORTS · '.$database->name, 'record_id' => $attachment->id, 'server_id' => $resourceServer->id, 'server_name' => $resourceServer->name, 'url' => $this->url.'?resource='.$attachment->id])
        ->and($nodes['resource-'.$cache->id]['type'])->toBe('redis')
        ->and($nodes->keys()->all())->not->toContain('server-'.$this->server->id, 'server-'.$resourceServer->id, 'domain-'.$domain->id)
        ->and(collect($topology['edges'])->map(fn ($edge) => [$edge['source'], $edge['target']])->all())->toContain(
            ['component-'.$lb->id, 'component-'.$app->id], ['component-'.$lb->id, 'external-'.$external->id],
            ['component-'.$app->id, 'resource-'.$attachment->id], ['component-'.$app->id, 'resource-'.$cache->id],
        );
    foreach ($nodes as $node) {
        expect($node)->toHaveKeys(['record_id', 'status', 'status_color', 'server_id', 'server_name', 'domains', 'url'])
            ->and($node['url'])->toStartWith('/')->not->toStartWith('//');
    }
    expect(json_encode($response->viewData('page')['props']))->not->toContain('attachment-secret', 'variable-secret', 'snapshot-secret', 'private-command');
    expect(SSH::getExecutedCommands())->toBeEmpty();
    Http::assertNothingSent();
});

test('empty overview and topology only reload remain read only and lazy', function () {
    $this->get($this->url)->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('topology', ['nodes' => [], 'edges' => []])->where('lastDeployment', null)
        ->reloadOnly('topology', fn (AssertableInertia $reload) => $reload->has('topology')->missing('primaryCandidates')->missing('lastDeployment')->missing('warnings')));
    Queue::assertNothingPushed();
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('topology tolerates a removed resource and ignores targets outside the environment or project', function () {
    $component = Component::factory()->for($this->environment)->for($this->server)->create();
    $foreign = Component::factory()->create();
    $component->backends()->create(['target_component_id' => $foreign->id, 'port' => 10000]);
    $foreignDomain = EnvironmentDomain::factory()->for($this->environment)->for($foreign)->create();
    Component::factory()->for($this->environment)->create(['server_id' => $foreign->server_id]);
    $database = Database::factory()->for($this->server)->create();
    $attachment = EnvironmentAttachment::factory()->for($this->environment)->create(['server_id' => $this->server->id, 'attachable_id' => $database->id]);
    $database->delete();

    $topology = app(GetEnvironmentTopology::class)->get($this->environment);
    expect(collect($topology['nodes'])->keyBy('id')['resource-'.$attachment->id]['name'])->toBe('Unavailable resource')
        ->and(collect($topology['nodes'])->pluck('id')->all())->not->toContain('component-'.$foreign->id, 'server-'.$foreign->server_id)
        ->and(collect($topology['nodes'])->pluck('domains')->flatten()->all())->not->toContain($foreignDomain->host)
        ->and($topology['edges'])->toHaveCount(1);
});

test('topology reads require authorization and scoped parents', function () {
    $this->user->currentProject->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->get($this->url)->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page->has('topology')->where('can.update', false));
    $other = Application::factory()->create(['project_id' => $this->server->project_id]);
    $this->get(route('environments.show', [$other, $this->environment]))->assertNotFound();
    $this->application->update(['project_id' => Project::factory()->create()->id]);
    $this->getJson($this->url)->assertForbidden();
    auth()->logout();
    $this->getJson($this->url)->assertUnauthorized();
});
