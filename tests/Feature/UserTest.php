<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('create user', function () {
    $this->actingAs($this->user);

    $this->post(route('users.store'), [
        'name' => 'new user',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'role' => UserRole::USER->value,
    ])
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('users'));

    $this->assertDatabaseHas('users', [
        'name' => 'new user',
        'email' => 'newuser@example.com',
        'is_admin' => false,
    ]);
});

test('see users list', function () {
    $this->actingAs($this->user);

    User::factory()->create();

    $this->get(route('users'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('users/index'));
});

test('must be admin to see users list', function () {
    $this->user->is_admin = false;
    $this->user->save();

    $this->actingAs($this->user);

    $this->get(route('users'))
        ->assertNotFound();
});

test('delete user', function () {
    $this->actingAs($this->user);

    $user = User::factory()->create();

    $this->delete(route('users.destroy', $user))
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('users'));

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('cannot delete yourself', function () {
    $this->actingAs($this->user);

    $this->delete(route('users.destroy', $this->user))
        ->assertForbidden();
});

test('edit user info', function () {
    $this->actingAs($this->user);

    $user = User::factory()->create();

    $this->patch(route('users.update', $user), [
        'name' => 'new-name',
        'email' => 'newemail@example.com',
        'role' => UserRole::ADMIN->value,
    ])
        ->assertRedirect(route('users'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'new-name',
        'email' => 'newemail@example.com',
        'is_admin' => true,
    ]);
});
