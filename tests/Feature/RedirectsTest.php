<?php

use App\Enums\RedirectStatus;
use App\Facades\SSH;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see redirects', function () {
    $this->actingAs($this->user);

    Redirect::factory()->create([
        'site_id' => $this->site->id,
    ]);

    $this->get(route('redirects', [
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('redirects/index'));
});

test('delete redirect', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $redirect = Redirect::factory()->create([
        'site_id' => $this->site->id,
    ]);

    $this->delete(route('redirects.destroy', [
        'server' => $this->server,
        'site' => $this->site,
        'redirect' => $redirect,
    ]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('redirects', [
        'id' => $redirect->id,
    ]);
});

test('create redirect', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('redirects.store', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'from' => 'some-path',
        'to' => 'https://example.com/redirect',
        'mode' => 301,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('redirects', [
        'from' => 'some-path',
        'to' => 'https://example.com/redirect',
        'mode' => 301,
        'status' => RedirectStatus::READY,
    ]);
});

test('create proxy redirect with websocket', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('redirects.store', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'from' => '/app',
        'to' => 'https://backend.example.com',
        'mode' => 1000,
        'websocket' => true,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('redirects', [
        'from' => '/app',
        'to' => 'https://backend.example.com',
        'mode' => 1000,
        'websocket' => true,
        'status' => RedirectStatus::READY,
    ]);
});

test('websocket forced off when not proxy mode', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('redirects.store', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'from' => '/app',
        'to' => 'https://example.com/redirect',
        'mode' => 301,
        'websocket' => true,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('redirects', [
        'from' => '/app',
        'mode' => 301,
        'websocket' => false,
    ]);
});

test('update redirect', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $redirect = Redirect::factory()->create([
        'site_id' => $this->site->id,
        'mode' => 301,
    ]);

    $this->put(route('redirects.update', [
        'server' => $this->server,
        'site' => $this->site,
        'redirect' => $redirect,
    ]), [
        'from' => 'updated-path',
        'to' => 'https://example.com/updated',
        'mode' => 302,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('redirects', [
        'id' => $redirect->id,
        'from' => 'updated-path',
        'to' => 'https://example.com/updated',
        'mode' => 302,
        'status' => RedirectStatus::READY,
    ]);
});

test('update redirect to proxy with websocket', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $redirect = Redirect::factory()->create([
        'site_id' => $this->site->id,
        'mode' => 301,
        'websocket' => false,
    ]);

    $this->put(route('redirects.update', [
        'server' => $this->server,
        'site' => $this->site,
        'redirect' => $redirect,
    ]), [
        'from' => 'ws-path',
        'to' => 'https://backend.example.com',
        'mode' => 1000,
        'websocket' => true,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('redirects', [
        'id' => $redirect->id,
        'mode' => 1000,
        'websocket' => true,
    ]);
});

test('update redirect away from proxy forces websocket off', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $redirect = Redirect::factory()->create([
        'site_id' => $this->site->id,
        'mode' => 1000,
        'websocket' => true,
    ]);

    $this->put(route('redirects.update', [
        'server' => $this->server,
        'site' => $this->site,
        'redirect' => $redirect,
    ]), [
        'from' => 'plain-path',
        'to' => 'https://example.com/plain',
        'mode' => 301,
        'websocket' => true,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('redirects', [
        'id' => $redirect->id,
        'mode' => 301,
        'websocket' => false,
    ]);
});
