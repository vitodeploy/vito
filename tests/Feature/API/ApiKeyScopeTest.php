<?php

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('full access token can see all projects', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    $project2->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $this->json('GET', '/api/projects')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $this->user->currentProject->id])
        ->assertJsonFragment(['id' => $project2->id]);
});

test('token with no project scope has access to all', function () {
    $token = $this->user->createToken('full-access-token', ['read', 'write']);

    /** @var PersonalAccessToken $accessToken */
    $accessToken = $token->accessToken;

    expect($accessToken->hasProjectAccess($this->user->currentProject))->toBeTrue();

    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    expect($accessToken->hasProjectAccess($project2))->toBeTrue();
    expect($accessToken->getProjectIds())->toBeEmpty();
});

test('scoped token has project ids in abilities', function () {
    $projectId = $this->user->current_project_id;
    $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$projectId]);

    /** @var PersonalAccessToken $accessToken */
    $accessToken = $token->accessToken;

    expect($accessToken->getProjectIds())->toEqual([$projectId]);
    expect($accessToken->hasProjectAccess($this->user->currentProject))->toBeTrue();
});

test('scoped token denies access to unscoped project', function () {
    /** @var Project $project1 */
    $project1 = Project::factory()->create();
    $project1->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    $project2->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$project1->id]);

    /** @var PersonalAccessToken $accessToken */
    $accessToken = $token->accessToken;

    expect($accessToken->hasProjectAccess($project1))->toBeTrue();
    expect($accessToken->hasProjectAccess($project2))->toBeFalse();
});

test('scoped token cannot access servers in unscoped project', function () {
    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    $project2->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    // Token scoped to project2 only
    $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$project2->id]);

    // Try accessing servers in the default project (not scoped)
    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('GET', route('api.projects.servers', [
            'project' => $this->user->current_project_id,
        ]))
        ->assertForbidden();
});

test('scoped token can access servers in scoped project', function () {
    $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$this->user->current_project_id]);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('GET', route('api.projects.servers', [
            'project' => $this->user->current_project_id,
        ]))
        ->assertSuccessful();
});

test('full access token can access any project servers', function () {
    $token = $this->user->createToken('full-access-token', ['read', 'write']);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('GET', route('api.projects.servers', [
            'project' => $this->user->current_project_id,
        ]))
        ->assertSuccessful();
});

test('read only token cannot write', function () {
    $token = $this->user->createToken('read-only-token', ['read']);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('POST', '/api/projects', [
            'name' => 'test-project',
        ])
        ->assertForbidden();
});

test('read only token can read', function () {
    $token = $this->user->createToken('read-only-token', ['read']);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('GET', '/api/projects')
        ->assertSuccessful();
});

test('scoped token filters project index', function () {
    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    $project2->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $token = $this->user->createToken('scoped-token', ['read', 'project:'.$project2->id]);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('GET', '/api/projects')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $project2->id])
        ->assertJsonMissing(['id' => $this->user->currentProject->id]);
});

test('scoped token cannot show unscoped project', function () {
    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    $project2->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $token = $this->user->createToken('scoped-token', ['read', 'project:'.$this->user->current_project_id]);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('GET', "/api/projects/{$project2->id}")
        ->assertForbidden();
});

test('scoped token can show scoped project', function () {
    $token = $this->user->createToken('scoped-token', ['read', 'project:'.$this->user->current_project_id]);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('GET', "/api/projects/{$this->user->currentProject->id}")
        ->assertSuccessful();
});

test('scoped token cannot update unscoped project', function () {
    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    $project2->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$this->user->current_project_id]);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('PUT', "/api/projects/{$project2->id}", [
            'name' => 'updated-name',
        ])
        ->assertForbidden();
});

test('scoped token can update scoped project', function () {
    $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$this->user->current_project_id]);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('PUT', "/api/projects/{$this->user->currentProject->id}", [
            'name' => 'updated-name',
        ])
        ->assertSuccessful();
});

test('scoped token cannot delete unscoped project', function () {
    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    $project2->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$this->user->current_project_id]);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('DELETE', "/api/projects/{$project2->id}", [
            'name' => $project2->name,
        ])
        ->assertForbidden();
});

test('scoped token can delete scoped project', function () {
    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    $project2->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::OWNER,
    ]);

    $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$project2->id]);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->json('DELETE', "/api/projects/{$project2->id}", [
            'name' => $project2->name,
        ])
        ->assertSuccessful();
});

test('multiple project scopes', function () {
    /** @var Project $project2 */
    $project2 = Project::factory()->create();
    $project2->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $token = $this->user->createToken('multi-scoped', [
        'read',
        'write',
        'project:'.$this->user->current_project_id,
        'project:'.$project2->id,
    ]);

    /** @var PersonalAccessToken $accessToken */
    $accessToken = $token->accessToken;

    expect($accessToken->hasProjectAccess($this->user->currentProject))->toBeTrue();
    expect($accessToken->hasProjectAccess($project2))->toBeTrue();
    expect($accessToken->getProjectIds())->toHaveCount(2);
});
