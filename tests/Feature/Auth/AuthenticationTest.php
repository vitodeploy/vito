<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
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
