<?php

use App\Models\SourceControl;
use App\Models\User;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\Gitlab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('connect provider', function (string $provider, array $input) {
    Sanctum::actingAs($this->user, ['read', 'write']);

    Http::fake();

    $input = array_merge([
        'name' => 'test',
        'provider' => $provider,
    ], $input);

    $this->json('POST', route('api.projects.source-controls.create', [
        'project' => $this->user->current_project_id,
    ]), $input)
        ->assertSuccessful()
        ->assertJsonFragment([
            'provider' => $provider,
            'name' => 'test',
        ]);
})->with('data');

test('delete provider', function (string $provider, array $input) {
    unset($input);

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => $provider,
        'profile' => 'test',
        'user_id' => $this->user->id,
    ]);

    $this->json('DELETE', route('api.projects.source-controls.delete', [
        'project' => $this->user->current_project_id,
        'sourceControl' => $sourceControl->id,
    ]))
        ->assertSuccessful()
        ->assertNoContent();
})->with('data');

test('cannot delete provider', function (string $provider, array $input) {
    unset($input);

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => $provider,
        'profile' => 'test',
        'user_id' => $this->user->id,
    ]);

    $this->site->update([
        'source_control_id' => $sourceControl->id,
    ]);

    $this->json('DELETE', route('api.projects.source-controls.delete', [
        'project' => $this->user->current_project_id,
        'sourceControl' => $sourceControl->id,
    ]))
        ->assertStatus(422)
        ->assertJsonFragment([
            'message' => 'This source control is being used by a site.',
        ]);

    $this->assertNotSoftDeleted('source_controls', [
        'id' => $sourceControl->id,
    ]);
})->with('data');

test('edit source control', function (string $provider, array $input) {
    Http::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => $provider,
        'profile' => 'old-name',
        'url' => $input['url'] ?? null,
        'user_id' => $this->user->id,
    ]);

    $this->json('PUT', route('api.projects.source-controls.update', [
        'project' => $this->user->current_project_id,
        'sourceControl' => $sourceControl->id,
    ]), array_merge([
        'name' => 'new-name',
    ], $input))
        ->assertSuccessful()
        ->assertJsonFragment([
            'provider' => $provider,
            'name' => 'new-name',
        ]);

    $sourceControl->refresh();

    expect($sourceControl->profile)->toEqual('new-name');
    if (isset($input['url'])) {
        expect($sourceControl->url)->toEqual($input['url']);
    }
})->with('data');

test('api user cannot access other users source control', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();
    $sourceControl = SourceControl::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->json('GET', route('api.projects.source-controls.show', [
        'project' => $this->user->current_project_id,
        'sourceControl' => $sourceControl->id,
    ]))
        ->assertForbidden();
});

test('api user cannot update other users source control', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();
    $sourceControl = SourceControl::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    Http::fake();

    $this->json('PUT', route('api.projects.source-controls.update', [
        'project' => $this->user->current_project_id,
        'sourceControl' => $sourceControl->id,
    ]), [
        'name' => 'hacked',
        'token' => 'hacked-token',
    ])
        ->assertForbidden();
});

test('api user cannot delete other users source control', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();
    $sourceControl = SourceControl::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->json('DELETE', route('api.projects.source-controls.delete', [
        'project' => $this->user->current_project_id,
        'sourceControl' => $sourceControl->id,
    ]))
        ->assertForbidden();
});

test('api guest cannot access source controls', function () {
    $sourceControl = SourceControl::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->json('GET', route('api.projects.source-controls', [
        'project' => $this->user->current_project_id,
    ]))
        ->assertUnauthorized();

    $this->json('POST', route('api.projects.source-controls.create', [
        'project' => $this->user->current_project_id,
    ]), [])
        ->assertUnauthorized();

    $this->json('DELETE', route('api.projects.source-controls.delete', [
        'project' => $this->user->current_project_id,
        'sourceControl' => $sourceControl->id,
    ]))
        ->assertUnauthorized();
});

test('api insufficient scopes denies access', function () {
    Sanctum::actingAs($this->user, ['read']);

    // Only read scope
    $data = [
        'provider' => Github::id(),
        'name' => 'test',
        'token' => 'fake-token',
    ];

    $this->json('POST', route('api.projects.source-controls.create', [
        'project' => $this->user->current_project_id,
    ]), $data)
        ->assertForbidden();
});

test('api cannot manipulate user id on creation', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();

    Http::fake();

    $data = [
        'provider' => Github::id(),
        'name' => 'test',
        'token' => 'fake-token',
        'user_id' => $otherUser->id,
    ];

    $this->json('POST', route('api.projects.source-controls.create', [
        'project' => $this->user->current_project_id,
    ]), $data)
        ->assertSuccessful()
        ->assertJsonFragment([
            'provider' => Github::id(),
            'name' => 'test',
        ]);

    $this->assertDatabaseHas('source_controls', [
        'profile' => 'test',
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);

    $this->assertDatabaseMissing('source_controls', [
        'profile' => 'test',
        'provider' => Github::id(),
        'user_id' => $otherUser->id,
    ]);
});

test('api user can only see own source controls in list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();

    $ownSourceControl = SourceControl::factory()->create([
        'user_id' => $this->user->id,
        'profile' => 'own-source-control',
    ]);

    $otherSourceControl = SourceControl::factory()->create([
        'user_id' => $otherUser->id,
        'profile' => 'other-source-control',
    ]);

    $this->json('GET', route('api.projects.source-controls', [
        'project' => $this->user->current_project_id,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'id' => $ownSourceControl->id,
            'provider' => $ownSourceControl->provider,
        ])
        ->assertJsonMissing([
            'id' => $otherSourceControl->id,
        ]);
});

test('api soft deleted source control cannot be accessed', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();
    $sourceControl = SourceControl::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $sourceControl->delete();

    $this->json('GET', route('api.projects.source-controls.show', [
        'project' => $this->user->current_project_id,
        'sourceControl' => $sourceControl->id,
    ]))
        ->assertNotFound();
});

/**
 * @return array<array<int, mixed>>
 */
dataset('data', function () {
    return [
        [Github::id(), ['token' => 'test']],
        [Github::id(), ['token' => 'test', 'global' => '1']],
        [Gitlab::id(), ['token' => 'test']],
        [Gitlab::id(), ['token' => 'test', 'url' => 'https://git.example.com/']],
        [Bitbucket::id(), ['username' => 'test', 'password' => 'test']],
    ];
});
