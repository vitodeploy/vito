<?php

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id]);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->component = Component::factory()->for($this->environment)->create(['server_id' => $this->server->id]);
});

test('application policies respect project roles', function (?UserRole $role, bool $read, bool $write) {
    $membership = $this->application->project->users()->where('user_id', $this->user->id);
    if ($role === null) {
        $membership->delete();
    } else {
        $membership->update(['role' => $role]);
    }
    $gate = Gate::forUser($this->user);

    $parents = [
        [Application::class, $this->application->project],
        [Environment::class, $this->application],
    ];
    $models = [
        [$this->application],
        [$this->environment, $this->application],
        [$this->component, $this->environment, $this->application],
    ];

    foreach ($parents as $arguments) {
        expect($gate->allows('viewAny', $arguments))->toBe($read)
            ->and($gate->allows('create', $arguments))->toBe($write);
    }
    expect($gate->allows('create', [Component::class, $this->environment, $this->application]))->toBe($write);
    foreach ($models as $arguments) {
        expect($gate->allows('view', $arguments))->toBe($read)
            ->and($gate->allows('update', $arguments))->toBe($write)
            ->and($gate->allows('delete', $arguments))->toBe($write);
    }
})->with([
    'owner' => [UserRole::OWNER, true, true],
    'admin' => [UserRole::ADMIN, true, true],
    'read only' => [UserRole::USER, true, false],
    'non member' => [null, false, false],
]);

test('application policies reject resources in other projects', function () {
    $other = Application::factory()->create();
    $environment = Environment::factory()->for($other)->create();
    $component = Component::factory()->for($environment)->create();
    $gate = Gate::forUser($this->user);

    foreach (['viewAny', 'create'] as $ability) {
        expect($gate->allows($ability, [Application::class, $other->project]))->toBeFalse()
            ->and($gate->allows($ability, [Environment::class, $other]))->toBeFalse()
            ->and($gate->allows($ability, [Component::class, $environment, $other]))->toBeFalse();
    }
    foreach (['view', 'update', 'delete'] as $ability) {
        expect($gate->allows($ability, $other))->toBeFalse()
            ->and($gate->allows($ability, [$environment, $other]))->toBeFalse()
            ->and($gate->allows($ability, [$component, $environment, $other]))->toBeFalse();
    }
});

test('child policies reject mismatched parents even within an authorized project', function () {
    $other = Application::factory()->create(['project_id' => $this->application->project_id]);
    $otherEnvironment = Environment::factory()->for($this->application)->create();
    $gate = Gate::forUser($this->user);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($gate->allows($ability, [$this->environment, $other]))->toBeFalse()
            ->and($gate->allows($ability, [$this->component, $this->environment, $other]))->toBeFalse()
            ->and($gate->allows($ability, [$this->component, $otherEnvironment, $this->application]))->toBeFalse();
    }
    foreach (['viewAny', 'create'] as $ability) {
        expect($gate->allows($ability, [Component::class, $this->environment, $other]))->toBeFalse();
    }
});

test('application policies honor api token project scopes', function (bool $sameProject) {
    $project = $sameProject ? $this->application->project : Project::factory()->create();
    $abilities = ['read', 'write', 'project:'.$project->id];
    $this->user->withAccessToken($this->user->createToken('applications', $abilities)->accessToken);
    $gate = Gate::forUser($this->user);

    foreach ([
        [$this->application],
        [$this->environment, $this->application],
        [$this->component, $this->environment, $this->application],
    ] as $arguments) {
        expect($gate->allows('view', $arguments))->toBe($sameProject)
            ->and($gate->allows('update', $arguments))->toBe($sameProject)
            ->and($gate->allows('delete', $arguments))->toBe($sameProject);
    }
})->with([true, false]);
