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

    // Enable two-factor authentication
    $this->post(route('two-factor.enable'))
        ->assertSessionDoesntHaveErrors();

    $user = $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();

    // Generate a valid TOTP code from the secret
    $google2fa = new Google2FA;
    $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

    // Submit the code to confirm 2FA
    $this->post(route('two-factor.confirm'), [
        'code' => $validCode,
    ])->assertSessionDoesntHaveErrors();

    // Assert the user is now confirmed
    expect($user->refresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('user can disable two factor authentication', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->ensureHasDefaultProject();

    $this->actingAs($user);

    // First, enable 2FA
    $this->post(route('two-factor.enable'))
        ->assertSessionDoesntHaveErrors();

    $user = $user->refresh();

    // Ensure 2FA secret is set
    expect($user->two_factor_secret)->not->toBeNull();

    // Now disable 2FA
    $this->delete(route('two-factor.disable'))
        ->assertSessionDoesntHaveErrors();

    $user = $user->refresh();

    // Ensure 2FA is fully removed
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

    // Should redirect to the two-factor challenge page
    $response->assertRedirect(route('two-factor.login'));

    // Simulate entering 2FA code
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
    // or your expected redirect route
    $this->assertAuthenticatedAs($user);
});
