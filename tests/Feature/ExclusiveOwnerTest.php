<?php

use App\Models\Component;
use App\Models\CronJob;
use App\Models\Deployment;
use App\Models\DeploymentScript;
use App\Models\GitHook;
use App\Models\Ssl;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('exclusive owner models', [
    'deployment' => [Deployment::class, ['site_id', 'component_id'], true],
    'script' => [DeploymentScript::class, ['site_id', 'environment_id', 'component_id'], true],
    'worker' => [Worker::class, ['site_id', 'component_id'], false],
    'cron job' => [CronJob::class, ['site_id', 'component_id'], false],
    'git hook' => [GitHook::class, ['site_id', 'repository'], true],
    'ssl' => [Ssl::class, ['site_id'], false],
]);

test('shared models enforce their zero owner contract', function (string $class, array $columns, bool $required) {
    $model = $class::factory()->make(array_fill_keys($columns, null));
    if (in_array($class, [Worker::class, CronJob::class, Ssl::class])) {
        $model->server_id = $this->server->id;
    }

    expect($model->ownerColumns())->toBe($columns)
        ->and($model->requiresOwner())->toBe($required);

    if ($required) {
        expect(fn () => $model->save())->toThrow(LogicException::class);

        return;
    }

    $model->save();
    $this->assertDatabaseHas($model->getTable(), array_merge(['id' => $model->id], array_fill_keys($columns, null)));
})->with('exclusive owner models');

test('shared models accept each single owner', function (string $class, array $columns) {
    $component = Component::factory()->create(['server_id' => $this->server->id]);
    $ids = [
        'site_id' => $this->site->id,
        'environment_id' => $component->environment_id,
        'component_id' => $component->id,
        'repository' => 'owner/repository',
    ];

    foreach ($columns as $column) {
        $model = $class::factory()->make(array_merge(array_fill_keys($columns, null), [$column => $ids[$column]]));
        if (in_array($class, [Worker::class, CronJob::class, Ssl::class])) {
            $model->server_id = $this->server->id;
        }
        $model->save();
        $this->assertDatabaseHas($model->getTable(), ['id' => $model->id, $column => $ids[$column]]);

        if ($column === 'site_id') {
            expect($model->site->is($this->site))->toBeTrue();
        } elseif ($column === 'environment_id') {
            expect($model->environment->is($component->environment))->toBeTrue();
        } elseif ($column === 'component_id') {
            expect($model->component->is($component))->toBeTrue();
        } else {
            expect($model->repository())->toBe($ids[$column]);
        }
    }
})->with('exclusive owner models');

test('shared models reject every pair of owners on create and update', function (string $class, array $columns) {
    $component = Component::factory()->create(['server_id' => $this->server->id]);
    $ids = [
        'site_id' => $this->site->id,
        'environment_id' => $component->environment_id,
        'component_id' => $component->id,
    ];

    foreach ($columns as $index => $first) {
        foreach (array_slice($columns, $index + 1) as $second) {
            $model = $class::factory()->make(array_merge(
                array_fill_keys($columns, null),
                [$first => $ids[$first], $second => $ids[$second]],
            ));
            if (in_array($class, [Worker::class, CronJob::class])) {
                $model->server_id = $this->server->id;
            }
            expect(fn () => $model->save())->toThrow(LogicException::class);

            $model->{$second} = null;
            $model->save();
            $model->{$second} = $ids[$second];
            expect(fn () => $model->save())->toThrow(LogicException::class);
            $this->assertDatabaseHas($model->getTable(), ['id' => $model->id, $first => $ids[$first], $second => null]);
        }
    }
})->with([
    [Deployment::class, ['site_id', 'component_id']],
    [DeploymentScript::class, ['site_id', 'environment_id', 'component_id']],
    [Worker::class, ['site_id', 'component_id']],
    [CronJob::class, ['site_id', 'component_id']],
]);

test('required owners cannot be cleared on existing models', function (string $class) {
    $model = $class::factory()->create(['site_id' => $this->site->id]);
    $model->site_id = null;

    expect(fn () => $model->save())->toThrow(LogicException::class);
    $this->assertDatabaseHas($model->getTable(), ['id' => $model->id, 'site_id' => $this->site->id]);
})->with([Deployment::class, DeploymentScript::class, GitHook::class]);

test('partial selects cannot add a second owner', function (string $class) {
    $component = Component::factory()->create(['server_id' => $this->server->id]);
    $model = $class::factory()->create(['site_id' => $this->site->id]);
    $partial = $class::query()->select('id', 'component_id')->findOrFail($model->id);
    $partial->component_id = $component->id;

    expect(fn () => $partial->save())->toThrow(LogicException::class);
    $this->assertDatabaseHas($model->getTable(), ['id' => $model->id, 'site_id' => $this->site->id, 'component_id' => null]);
})->with([Deployment::class, DeploymentScript::class, Worker::class, CronJob::class]);

test('partial selects can save unrelated changes without losing their required owner', function (string $class) {
    $model = $class::factory()->create(['site_id' => $this->site->id]);
    $columns = $class === DeploymentScript::class ? ['id', 'content'] : ['id'];
    $partial = $class::query()->select($columns)->findOrFail($model->id);
    $partial->updated_at = now()->addMinute();
    $partial->save();

    $this->assertDatabaseHas($model->getTable(), ['id' => $model->id, 'site_id' => $this->site->id]);
})->with([Deployment::class, DeploymentScript::class, GitHook::class]);

test('partial selects preserve explicit owner changes', function () {
    $component = Component::factory()->create(['server_id' => $this->server->id]);
    $script = DeploymentScript::factory()->create(['site_id' => $this->site->id]);
    $partial = DeploymentScript::query()->select('id', 'content')->findOrFail($script->id);
    $partial->site_id = null;
    $partial->component_id = $component->id;
    $partial->save();

    $this->assertDatabaseHas('deployment_scripts', ['id' => $script->id, 'site_id' => null, 'component_id' => $component->id]);
});

test('owner checks count non-null values rather than truthy ids', function () {
    $worker = Worker::factory()->make(['site_id' => 0, 'component_id' => 1]);

    expect(fn () => $worker->save())->toThrow(LogicException::class);
});
