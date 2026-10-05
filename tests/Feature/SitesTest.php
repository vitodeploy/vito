<?php

use App\Enums\ServiceStatus;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Facades\SSH;
use App\Jobs\Site\CreateJob;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Project;
use App\Models\Server;
use App\Models\Service;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\User;
use App\SiteTypes\Blank;
use App\SiteTypes\Laravel;
use App\SiteTypes\PHPBlank;
use App\SiteTypes\Wordpress;
use App\SourceControlProviders\Github;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('site settings rejects outsiders and mismatched servers', function (bool $outsider) {
    if ($outsider) {
        $this->server->project->users()->where('user_id', $this->user->id)->delete();
    }

    $this->actingAs($this->user)->get(route('site-settings', [
        'server' => $outsider ? $this->server : Server::factory()->create(),
        'site' => $this->site,
    ]))->assertForbidden();
})->with([true, false]);

test('blank sites discard disabled source control input', function (?bool $enabled) {
    SSH::fake();
    Http::fake();
    Queue::fake();

    $sourceControl = SourceControl::factory()->create(['user_id' => User::factory()->create()->id]);
    $input = [
        'type' => Blank::id(),
        'domain' => 'blank-disabled.example.com',
        'port' => 3000,
        'user' => 'blankdisabled',
        'source_control' => $sourceControl->id,
        'repository' => 'private/repository',
        'branch' => 'private',
    ];
    if ($enabled !== null) {
        $input['use_source_control'] = $enabled;
    }

    $this->actingAs($this->user)->post(route('sites.store', ['server' => $this->server]), $input)
        ->assertRedirect()->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'domain' => $input['domain'],
        'source_control_id' => null,
        'repository' => '',
        'branch' => '',
    ]);
    Queue::assertPushed(CreateJob::class, 1);
    Http::assertNothingSent();
})->with([false, null]);

test('site source selection uses the actor rather than the server creator', function (string $operation, string $scope) {
    SSH::fake();
    Http::fake();
    Queue::fake();

    $creator = User::factory()->create();
    $this->server->update(['user_id' => $creator->id]);
    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::ADMIN]);
    $sourceControl = SourceControl::factory()->create([
        'user_id' => $scope === 'creator' ? $creator->id : $this->user->id,
        'project_id' => match ($scope) {
            'project' => $this->server->project_id,
            'foreign-project' => Project::factory()->create()->id,
            default => null,
        },
    ]);
    $originalSource = $this->site->source_control_id;
    $this->actingAs($this->user);

    $response = $operation === 'create'
        ? $this->post(route('sites.store', ['server' => $this->server]), [
            'type' => Blank::id(),
            'domain' => 'actor-source.example.com',
            'port' => 3000,
            'user' => 'actorsource',
            'use_source_control' => true,
            'source_control' => $sourceControl->id,
            'repository' => 'private/repository',
            'branch' => 'main',
        ])
        : $this->patch(route('site-settings.update-source-control', [
            'server' => $this->server,
            'site' => $this->site,
        ]), ['source_control' => $sourceControl->id]);

    if (in_array($scope, ['creator', 'foreign-project'])) {
        if ($scope === 'creator') {
            $response->assertForbidden();
        } else {
            $response->assertSessionHasErrors('source_control');
        }
        $this->assertDatabaseHas('sites', ['id' => $this->site->id, 'source_control_id' => $originalSource]);
        $this->assertDatabaseMissing('sites', ['domain' => 'actor-source.example.com']);
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    } else {
        $response->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('sites', ['source_control_id' => $sourceControl->id]);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer '.$sourceControl->access_token));
    }
})->with(['create', 'update'])->with(['creator', 'global', 'project', 'foreign-project']);

test('web site resources hide installation passwords from read-only users', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $typeData = [
        'title' => 'Private WordPress',
        'password' => 'wordpress-install-secret',
        'database_password' => 'database-install-secret',
    ];
    $this->site->update(['type' => Wordpress::id(), 'type_data' => $typeData]);

    $this->actingAs($this->user)->get(route('site-settings', [
        'server' => $this->server,
        'site' => $this->site,
    ]))->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('site.type_data.title', $typeData['title'])
        ->missing('site.type_data.password')
        ->missing('site.type_data.database_password')
    );

    expect($this->site->fresh()->type_data)->toBe($typeData);
});

test('create site', function (array $inputs) {
    SSH::fake();

    Http::fake([
        'https://api.github.com/repos/*' => Http::response([
        ], 201),
    ]);

    if (isset($inputs['database']) && isset($inputs['database_user'])) {
        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server->id,
        ]);
        /** @var DatabaseUser $databaseUser */
        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server->id,
        ]);
        $inputs['database'] = $database->id;
        $inputs['database_user'] = $databaseUser->id;
    }

    $this->actingAs($this->user);

    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);

    $inputs['source_control'] = $sourceControl->id;

    $this->post(route('sites.store', ['server' => $this->server]), $inputs)
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'domain' => $inputs['domain'],
        'status' => SiteStatus::READY->value,
        'user' => $inputs['user'],
        'path' => '/home/'.$inputs['user'].'/'.$inputs['domain'],
    ]);
})->with('create_data');

test('isolated user failure', function (array $inputs) {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), $inputs)
        ->assertSessionHasErrors();
})->with('failure_create_data');

test('create site reusing existing isolated user', function () {
    SSH::fake();

    $this->actingAs($this->user);

    Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shared',
        'domain' => 'first.example.com',
        'path' => '/home/shared/first.example.com',
        'php_version' => '8.2',
    ]);

    $this->post(route('sites.store', ['server' => $this->server]), [
        'type' => PHPBlank::id(),
        'domain' => 'second.example.com',
        'aliases' => [],
        'php_version' => '8.2',
        'web_directory' => 'public',
        'user' => 'shared',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'domain' => 'first.example.com',
        'user' => 'shared',
    ]);
    $this->assertDatabaseHas('sites', [
        'domain' => 'second.example.com',
        'user' => 'shared',
        'path' => '/home/shared/second.example.com',
    ]);

    SSH::assertExecutedContains('User shared already exists');
});

test('isolated users endpoint lists users with counts', function () {
    $this->actingAs($this->user);

    Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shop',
        'domain' => 'shop1.test',
        'path' => '/home/shop/shop1.test',
    ]);
    Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shop',
        'domain' => 'shop2.test',
        'path' => '/home/shop/shop2.test',
    ]);
    Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'blog',
        'domain' => 'blog.test',
        'path' => '/home/blog/blog.test',
    ]);

    $response = $this->getJson(route('sites.isolated-users', ['server' => $this->server]));

    $response->assertSuccessful();
    $data = collect($response->json())->keyBy('user');

    expect($data['shop']['sites_count'])->toBe(2);
    expect($data['blog']['sites_count'])->toBe(1);
    $this->assertArrayNotHasKey('vito', $data->all());
});

test('delete site keeps isolated user when others share it', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $siteA = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shared',
        'domain' => 'a.test',
        'path' => '/home/shared/a.test',
        'php_version' => '8.2',
    ]);
    Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shared',
        'domain' => 'b.test',
        'path' => '/home/shared/b.test',
        'php_version' => '8.2',
    ]);

    $this->delete(route('site-settings.destroy', [
        'server' => $this->server->id,
        'site' => $siteA->id,
    ]), [
        'domain' => $siteA->domain,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('sites', ['id' => $siteA->id]);
    $this->assertDatabaseHas('sites', ['domain' => 'b.test', 'user' => 'shared']);

    SSH::assertNotExecutedContains('userdel');
});

test('delete last isolated site removes user', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'lonely',
        'domain' => 'lonely.test',
        'path' => '/home/lonely/lonely.test',
        'php_version' => '8.2',
    ]);

    $this->delete(route('site-settings.destroy', [
        'server' => $this->server->id,
        'site' => $site->id,
    ]), [
        'domain' => $site->domain,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('sites', ['id' => $site->id]);

    SSH::assertExecutedContains('userdel');
});

test('php version switch removes old pool when not shared', function () {
    SSH::fake();

    $this->actingAs($this->user);

    Service::query()->create([
        'server_id' => $this->server->id,
        'type' => 'php',
        'name' => 'php',
        'version' => '8.4',
        'status' => ServiceStatus::READY,
    ]);

    Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shared',
        'domain' => 'a.test',
        'path' => '/home/shared/a.test',
        'php_version' => '8.2',
    ]);
    $siteB = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shared',
        'domain' => 'b.test',
        'path' => '/home/shared/b.test',
        'php_version' => '8.4',
    ]);

    $this->patch(route('site-settings.update-php-version', [
        'server' => $this->server->id,
        'site' => $siteB->id,
    ]), [
        'version' => '8.2',
    ])->assertSessionDoesntHaveErrors();

    $siteB->refresh();
    expect($siteB->php_version)->toBe('8.2');

    SSH::assertExecutedContains('rm -f /etc/php/8.4/fpm/pool.d/shared.conf');
});

test('php version switch preserves shared old pool', function () {
    SSH::fake();

    $this->actingAs($this->user);

    Service::query()->create([
        'server_id' => $this->server->id,
        'type' => 'php',
        'name' => 'php',
        'version' => '8.4',
        'status' => ServiceStatus::READY,
    ]);

    Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shared',
        'domain' => 'a.test',
        'path' => '/home/shared/a.test',
        'php_version' => '8.2',
    ]);
    $siteB = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'shared',
        'domain' => 'b.test',
        'path' => '/home/shared/b.test',
        'php_version' => '8.2',
    ]);

    $this->patch(route('site-settings.update-php-version', [
        'server' => $this->server->id,
        'site' => $siteB->id,
    ]), [
        'version' => '8.4',
    ])->assertSessionDoesntHaveErrors();

    $siteB->refresh();
    expect($siteB->php_version)->toBe('8.4');

    SSH::assertNotExecutedContains('rm -f /etc/php/8.2/fpm/pool.d/shared.conf');
});

test('create site failed due to source control', function (int $status) {
    $inputs = [
        'type' => Laravel::id(),
        'domain' => 'example.com',
        'php_version' => '8.2',
        'web_directory' => 'public',
        'repository' => 'test/test',
        'branch' => 'main',
        'composer' => true,
        'user' => 'example',
    ];

    SSH::fake();

    Http::fake([
        'https://api.github.com/repos/*' => Http::response([
        ], $status),
    ]);

    $this->actingAs($this->user);

    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);

    $inputs['source_control'] = $sourceControl->id;

    $this->post(route('sites.store', ['server' => $this->server]), $inputs)
        ->assertSessionHasErrors();

    $this->assertDatabaseMissing('sites', [
        'domain' => 'example.com',
        'status' => SiteStatus::READY,
    ]);
})->with('create_failure_data');

test('create blank site without source control', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), [
        'type' => Blank::id(),
        'domain' => 'blank-example.com',
        'port' => '3000',
        'user' => 'blanktest',
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'domain' => 'blank-example.com',
        'status' => SiteStatus::READY->value,
        'user' => 'blanktest',
        'source_control_id' => null,
    ]);
});

test('create blank site with source control requires repository', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);

    $this->post(route('sites.store', ['server' => $this->server]), [
        'type' => Blank::id(),
        'domain' => 'blank-sc.com',
        'port' => '3000',
        'user' => 'blanksc',
        'use_source_control' => true,
        'source_control' => $sourceControl->id,
    ])->assertSessionHasErrors(['repository', 'branch']);
});

test('create laravel site dispatches ensure env script', function () {
    SSH::fake();
    Http::fake([
        'https://api.github.com/repos/*' => Http::response([], 201),
    ]);

    $this->actingAs($this->user);

    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);

    $this->post(route('sites.store', ['server' => $this->server]), [
        'type' => Laravel::id(),
        'domain' => 'env-example.com',
        'php_version' => '8.2',
        'web_directory' => 'public',
        'repository' => 'test/test',
        'branch' => 'main',
        'composer' => false,
        'node_version' => 'none',
        'user' => 'envtest',
        'source_control' => $sourceControl->id,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'domain' => 'env-example.com',
        'status' => SiteStatus::READY->value,
        'user' => 'envtest',
        'path' => '/home/envtest/env-example.com',
    ]);

    $envPath = '/home/envtest/env-example.com/.env';
    $examplePath = '/home/envtest/env-example.com/.env.example';

    SSH::assertExecutedContains("[ -f '{$envPath}' ]");
    SSH::assertExecutedContains("cp -- '{$examplePath}' '{$envPath}'");
    SSH::assertExecutedContains("touch -- '{$envPath}'");
    SSH::assertExecutedContains("chmod 640 -- '{$envPath}'");
});

test('see sites list', function () {
    $this->actingAs($this->user);

    Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->get(route('sites', [
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('sites/index'));
});

test('delete site', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->delete(route('site-settings.destroy', [
        'server' => $this->server->id,
        'site' => $site->id,
    ]), [
        'domain' => $site->domain,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('sites', [
        'id' => $site->id,
    ]);
});

test('change php version', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'php_version' => '8.3',
    ]);

    $this->patch(route('site-settings.update-php-version', [
        'server' => $this->server->id,
        'site' => $site->id,
    ]), [
        'version' => '8.2',
    ])
        ->assertSessionDoesntHaveErrors();

    $site->refresh();

    expect($site->php_version)->toEqual('8.2');
});

test('update source control', function () {
    SSH::fake();

    $this->actingAs($this->user);

    Http::fake([
        'https://api.github.com/repos/*' => Http::response([
        ], 201),
    ]);

    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);

    $this->patch(route('site-settings.update-source-control', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'source_control' => $sourceControl->id,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->site->refresh();

    expect($this->site->source_control_id)->toEqual($sourceControl->id);
});

test('failed to update source control', function () {
    SSH::fake();

    $this->actingAs($this->user);

    Http::fake([
        'https://api.github.com/repos/*' => Http::response([
        ], 404),
    ]);

    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);

    $this->patch(route('site-settings.update-source-control', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'source_control' => $sourceControl->id,
    ])
        ->assertSessionHasErrors();
});

test('update v host', function () {
    SSH::fake();

    $this->actingAs($this->user);

    Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->patch(route('site-settings.update-vhost', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'vhost' => 'test',
    ])
        ->assertSessionDoesntHaveErrors();
});

test('see logs', function () {
    $this->actingAs($this->user);

    $this->get(route('sites.logs', [
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('sites/logs'));
});

test('change branch', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-branch', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'branch' => 'master',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->site->refresh();
    expect($this->site->branch)->toEqual('master');

    SSH::assertExecutedContains("git checkout -f 'master'");
});

test('change branch with modern deployment checks out the source, not the live release', function () {
    SSH::fake();

    $this->site->update([
        'path' => '/home/vito/vito.test/current',
        'type_data' => [
            'modern_deployment' => true,
            'modern_deployment_history' => 10,
            'modern_deployment_shared_resources' => ['.env', 'storage'],
        ],
    ]);

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-branch', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'branch' => 'develop',
    ])
        ->assertSessionDoesntHaveErrors();

    expect($this->site->refresh()->branch)->toEqual('develop');

    SSH::assertExecutedContains("cd '/home/vito/vito.test/source'");
    SSH::assertExecutedContains("git checkout -f 'develop'");
    SSH::assertNotExecutedContains("cd '/home/vito/vito.test/current'");
});

test('update web directory', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-web-directory', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'web_directory' => 'public',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->site->refresh();
    expect($this->site->web_directory)->toEqual('public');
});

test('update web directory empty', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-web-directory', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'web_directory' => '',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->site->refresh();
    expect($this->site->web_directory)->toBeNull();
});

test('update web directory normalizes slashes', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-web-directory', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'web_directory' => '/public/dist/',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->site->refresh();
    expect($this->site->web_directory)->toEqual('public/dist');
});

test('update web directory normalizes root', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-web-directory', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'web_directory' => '/',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->site->refresh();
    expect($this->site->web_directory)->toBeNull();
});

test('update web directory rejects invalid characters', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-web-directory', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'web_directory' => 'public@invalid!',
    ])
        ->assertSessionHasErrors(['web_directory']);
});

test('update web directory rejects directory traversal', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->patch(route('site-settings.update-web-directory', [
        'server' => $this->server->id,
        'site' => $this->site,
    ]), [
        'web_directory' => '../etc/passwd',
    ])
        ->assertSessionHasErrors(['web_directory']);
});

test('create site with valid web directory', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), [
        'type' => PHPBlank::id(),
        'domain' => 'example.com',
        'php_version' => '8.2',
        'web_directory' => 'public/dist',
        'user' => 'example',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'domain' => 'example.com',
        'web_directory' => 'public/dist',
    ]);
});

test('create site with special characters web directory', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), [
        'type' => PHPBlank::id(),
        'domain' => 'example.com',
        'php_version' => '8.2',
        'web_directory' => 'public-dist_v1.0',
        'user' => 'example',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'domain' => 'example.com',
        'web_directory' => 'public-dist_v1.0',
    ]);
});

test('create site normalizes web directory slashes', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), [
        'type' => PHPBlank::id(),
        'domain' => 'example.com',
        'php_version' => '8.2',
        'web_directory' => '/public/',
        'user' => 'example',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'domain' => 'example.com',
        'web_directory' => 'public',
    ]);
});

test('create site normalizes root web directory', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), [
        'type' => PHPBlank::id(),
        'domain' => 'example.com',
        'php_version' => '8.2',
        'web_directory' => '/',
        'user' => 'example',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sites', [
        'domain' => 'example.com',
        'web_directory' => null,
    ]);
});

test('create site rejects invalid web directory characters', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), [
        'type' => PHPBlank::id(),
        'domain' => 'example.com',
        'php_version' => '8.2',
        'web_directory' => 'public@invalid!',
        'user' => 'example',
    ])
        ->assertSessionHasErrors(['web_directory']);

    $this->assertDatabaseMissing('sites', [
        'domain' => 'example.com',
    ]);
});

test('create site rejects directory traversal', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), [
        'type' => PHPBlank::id(),
        'domain' => 'example.com',
        'php_version' => '8.2',
        'web_directory' => '../etc/passwd',
        'user' => 'example',
    ])
        ->assertSessionHasErrors(['web_directory']);

    $this->assertDatabaseMissing('sites', [
        'domain' => 'example.com',
    ]);
});

dataset('failure_create_data', /** @return array<int, array{0: array<string, mixed>}> */ function (): array {
    return [
        [
            [
                'type' => PHPBlank::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'user' => 'a',
            ],
        ],
        [
            [
                'type' => PHPBlank::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'user' => 'root',
            ],
        ],
        [
            [
                'type' => PHPBlank::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'user' => 'vito',
            ],
        ],
        [
            [
                'type' => PHPBlank::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'user' => '123',
            ],
        ],
        [
            [
                'type' => PHPBlank::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'user' => 'qwertyuiopasdfghjklzxcvbnmqwertyu',
            ],
        ],
        [
            [
                'type' => PHPBlank::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'user' => 'www-data',
            ],
        ],
        [
            [
                'type' => PHPBlank::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'user' => 'mysql',
            ],
        ],
        [
            [
                'type' => PHPBlank::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'user' => 'ubuntu',
            ],
        ],
    ];
});

dataset('create_data', /** @return array<int, array{0: array<string, mixed>}> */ function (): array {
    return vitoPestSiteCreateData();
});

dataset('create_failure_data', /** @return array<int, array{0: int}> */ function (): array {
    return [
        [401],
        [403],
        [404],
    ];
});
