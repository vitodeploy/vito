<?php

use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('create api key with read permission', function () {
    $this->actingAs($this->user)
        ->post(route('api-keys.store'), [
            'name' => 'test-key',
            'permission' => 'read',
        ])
        ->assertSessionHas('success');

    $this->assertDatabaseHas('personal_access_tokens', [
        'name' => 'test-key',
        'tokenable_id' => $this->user->id,
    ]);
});

test('create api key with write permission', function () {
    $this->actingAs($this->user)
        ->post(route('api-keys.store'), [
            'name' => 'test-key',
            'permission' => 'write',
        ])
        ->assertSessionHas('success');

    $token = $this->user->tokens()->where('name', 'test-key')->first();
    expect($token->abilities)->toContain('read');
    expect($token->abilities)->toContain('write');
});

test('create api key with project scope', function () {
    $this->actingAs($this->user)
        ->post(route('api-keys.store'), [
            'name' => 'scoped-key',
            'permission' => 'read',
            'projects' => [$this->user->current_project_id],
        ])
        ->assertSessionHas('success');

    $token = $this->user->tokens()->where('name', 'scoped-key')->first();
    expect($token->abilities)->toContain('read');
    expect($token->abilities)->toContain('project:'.$this->user->current_project_id);
});

test('create api key without project scope', function () {
    $this->actingAs($this->user)
        ->post(route('api-keys.store'), [
            'name' => 'full-access-key',
            'permission' => 'write',
        ])
        ->assertSessionHas('success');

    $token = $this->user->tokens()->where('name', 'full-access-key')->first();
    expect($token->abilities)->toContain('read');
    expect($token->abilities)->toContain('write');
    expect(collect($token->abilities)->filter(fn ($a) => str_starts_with($a, 'project:'))->all())->toBeEmpty();
});

test('create api key with multiple projects', function () {
    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    $project2->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $this->actingAs($this->user)
        ->post(route('api-keys.store'), [
            'name' => 'multi-project-key',
            'permission' => 'write',
            'projects' => [$this->user->current_project_id, $project2->id],
        ])
        ->assertSessionHas('success');

    $token = $this->user->tokens()->where('name', 'multi-project-key')->first();
    expect($token->abilities)->toContain('project:'.$this->user->current_project_id);
    expect($token->abilities)->toContain('project:'.$project2->id);
});

test('cannot create api key with invalid project', function () {
    $this->actingAs($this->user)
        ->post(route('api-keys.store'), [
            'name' => 'bad-key',
            'permission' => 'read',
            'projects' => [999999],
        ])
        ->assertSessionHasErrors('projects.0');
});

test('cannot create api key with project user does not belong to', function () {
    /** @var Project $otherProject */
    $otherProject = Project::factory()->create();

    $this->actingAs($this->user)
        ->post(route('api-keys.store'), [
            'name' => 'bad-key',
            'permission' => 'read',
            'projects' => [$otherProject->id],
        ])
        ->assertSessionHasErrors('projects.0');
});

test('delete api key', function () {
    $token = $this->user->createToken('delete-me', ['read']);

    $this->actingAs($this->user)
        ->delete(route('api-keys.destroy', $token->accessToken->id))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);
});

test('index returns api keys', function () {
    $this->user->createToken('test-key', ['read', 'project:'.$this->user->current_project_id]);

    $this->actingAs($this->user)
        ->get(route('api-keys'))
        ->assertSuccessful();
});

test('index returns project ids and filtered permissions', function () {
    $this->user->createToken('scoped-key', ['read', 'write', 'project:'.$this->user->current_project_id]);

    $response = $this->actingAs($this->user)
        ->get(route('api-keys'))
        ->assertSuccessful();

    $apiKeys = $response->original->getData()['page']['props']['apiKeys']['data'];
    $key = collect($apiKeys)->firstWhere('name', 'scoped-key');

    expect($key)->not->toBeNull();
    expect($key['permissions'])->toEqual(['read', 'write']);
    expect($key['project_ids'])->toEqual([$this->user->current_project_id]);
});

test('create api key with empty projects array', function () {
    $this->actingAs($this->user)
        ->post(route('api-keys.store'), [
            'name' => 'empty-projects-key',
            'permission' => 'read',
            'projects' => [],
        ])
        ->assertSessionHas('success');

    $token = $this->user->tokens()->where('name', 'empty-projects-key')->first();
    expect($token->abilities)->toContain('read');
    expect(collect($token->abilities)->filter(fn ($a) => str_starts_with($a, 'project:'))->all())->toBeEmpty();
});

test('cannot create api key with invalid permission', function () {
    $this->actingAs($this->user)
        ->post(route('api-keys.store'), [
            'name' => 'bad-key',
            'permission' => 'admin',
        ])
        ->assertSessionHasErrors('permission');
});
