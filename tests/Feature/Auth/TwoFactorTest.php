<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

test('user can enable two factor authentication', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->ensureHasDefaultProject();

    $this->actingAs($user);

    $this->post(route('two-factor.enable'))
        ->assertSessionDoesntHaveErrors();

    $user = $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();

    $google2fa = new Google2FA;
    $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

    $this->post(route('two-factor.confirm'), [
        'code' => $validCode,
    ])->assertSessionDoesntHaveErrors();

    expect($user->refresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('user can disable two factor authentication', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->ensureHasDefaultProject();

    $this->actingAs($user);

    $this->post(route('two-factor.enable'))
        ->assertSessionDoesntHaveErrors();

    $user = $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull();

    $this->delete(route('two-factor.disable'))
        ->assertSessionDoesntHaveErrors();

    $user = $user->refresh();

    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->two_factor_recovery_codes ?? [])->toBeEmpty();
});

test('see two factor challenge', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'two_factor_secret' => encrypt((new Google2FA)->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);
    $user->ensureHasDefaultProject();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));

    $loginId = session('login.id');
    expect($loginId)->not->toBeNull();

    $user->refresh();
    $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));

    $response = $this->withSession([
        'login.id' => $loginId,
    ])->post(route('two-factor.login.store'), [
        'code' => $code,
    ])
        ->assertSessionDoesntHaveErrors();

    $response->assertRedirect(route('servers'));
    $this->assertAuthenticatedAs($user);
});
