<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('confirm password screen can be rendered', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->ensureHasDefaultProject();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertStatus(200);
});

test('password can be confirmed', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->ensureHasDefaultProject();

    $response = $this->actingAs($user)->post(route('password.confirm'), [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
});

test('password is not confirmed with invalid password', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->ensureHasDefaultProject();

    $response = $this->actingAs($user)->post(route('password.confirm'), [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
});
