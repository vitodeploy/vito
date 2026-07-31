<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('profile page is displayed', function () {
    $this->actingAs($this->user);

    test()
        ->get(route('profile'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('profile/index'));
});

test('profile information can be updated', function () {
    $this->actingAs($this->user);

    $this->patch(route('profile.update'), [
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
        ->assertRedirect(route('profile'));

    $this->user->refresh();

    expect($this->user->name)->toBe('Test');
    expect($this->user->email)->toBe('test@example.com');
});

test('password can be updated', function () {
    $this->actingAs($this->user);

    $this->put(route('profile.password'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertRedirect(route('profile'))
        ->assertSessionDoesntHaveErrors();

    expect(Hash::check('new-password', $this->user->refresh()->password))->toBeTrue();

    $this->user->refresh();
    expect($this->user->two_factor_secret)->toBeNull();
    expect($this->user->two_factor_recovery_codes)->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
});

test('correct password must be provided to update password', function () {
    $this->actingAs($this->user);
    $this->put(route('profile.password'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertSessionHasErrors('current_password');
});
