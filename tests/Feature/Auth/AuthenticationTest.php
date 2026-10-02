<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Ssr\Gateway;
use Inertia\Testing\AssertableInertia;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('login screen can be rendered', function (bool $ssrEnabled) {
    config()->set('inertia.ssr.enabled', $ssrEnabled);
    $this->mock(Gateway::class, function (MockInterface $mock): void {
        $mock->shouldReceive('dispatch')->andReturnNull();
    });

    $this->get('/login')
        ->assertSuccessful()
        ->assertDontSee('/ziggy/', false)
        ->assertInertia(fn (AssertableInertia $page) => $page->component('auth/login')->missing('ziggy'));
})->with([false, true]);

test('client route catalogue is not served', function () {
    $this->get('/ziggy/deadbeef.js')->assertNotFound();
});

test('users can authenticate using the login screen', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('servers', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->ensureHasDefaultProject();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});
