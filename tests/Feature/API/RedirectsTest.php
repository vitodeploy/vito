<?php

use App\Enums\RedirectStatus;
use App\Facades\SSH;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('create redirect', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.sites.redirects.create', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'from' => 'testing/path',
        'to' => 'https://example.com',
        'mode' => 301,
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'from' => 'testing/path',
            'to' => 'https://example.com',
            'mode' => 301,
            'status' => RedirectStatus::READY,
        ]);
});

test('update redirect', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('PUT', route('api.projects.servers.sites.redirects.update', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
        'redirect' => $this->redirect->id,
    ]), [
        'from' => 'updated/path',
        'to' => 'https://updated.example.com',
        'mode' => 302,
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'id' => $this->redirect->id,
            'from' => 'updated/path',
            'to' => 'https://updated.example.com',
            'mode' => 302,
            'status' => RedirectStatus::READY,
        ]);
});

test('see redirects list', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Redirect $redirect */
    $redirect = Redirect::factory()->create([
        'site_id' => $this->site->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites.redirects.index', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'from' => $redirect->from,
            'to' => $redirect->to,
            'mode' => $redirect->mode,
            'status' => $redirect->status,
        ]);
});

test('delete redirect', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['write']);

    $this->json('DELETE', route('api.projects.servers.sites.redirects.delete', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
        'redirect' => $this->redirect->id,
    ]))
        ->assertSuccessful()
        ->assertNoContent();

    $this->assertDatabaseMissing('redirects', [
        'id' => $this->redirect->id,
    ]);
});
