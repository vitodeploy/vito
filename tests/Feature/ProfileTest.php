<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function vitoPestFeatureProfileTestEnableTwoFactor(User $user): void
{
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
        'two_factor_confirmed_at' => now(),
    ])->save();
}

test('profile page is displayed without recovery codes', function () {
    vitoPestFeatureProfileTestEnableTwoFactor($this->user);
    $this->actingAs($this->user);

    $this->get(route('profile'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('profile/index')
            ->where('two_factor_enabled', true)
            ->missing('two_factor_recovery_codes'));
});

test('profile name can be updated without the password', function () {
    $this->actingAs($this->user);

    $this->put(route('user-profile-information.update'), [
        'name' => 'Test',
        'email' => $this->user->email,
    ])->assertSessionHasNoErrors();

    expect($this->user->refresh()->name)->toBe('Test');
});

test('changing the email requires the current password', function () {
    $this->actingAs($this->user);
    $email = $this->user->email;

    $this->put(route('user-profile-information.update'), [
        'name' => 'Test',
        'email' => 'test@example.com',
    ])->assertSessionHasErrorsIn('updateProfileInformation', 'current_password');

    $this->put(route('user-profile-information.update'), [
        'name' => 'Test',
        'email' => 'test@example.com',
        'current_password' => 'wrong-password',
    ])->assertSessionHasErrorsIn('updateProfileInformation', 'current_password');

    expect($this->user->refresh()->email)->toBe($email);
});

test('a malformed current password is a validation error', function () {
    $this->actingAs($this->user);

    $this->put(route('user-profile-information.update'), [
        'name' => 'Test',
        'email' => 'test@example.com',
        'current_password' => ['password'],
    ])->assertSessionHasErrorsIn('updateProfileInformation', 'current_password');
});

test('a stale current password is ignored when the email is unchanged', function () {
    $this->actingAs($this->user);

    $this->put(route('user-profile-information.update'), [
        'name' => 'Test',
        'email' => $this->user->email,
        'current_password' => 'wrong-password',
    ])->assertSessionHasNoErrors();

    expect($this->user->refresh()->name)->toBe('Test');
});

test('email can be changed with the current password', function () {
    $this->actingAs($this->user);

    $this->put(route('user-profile-information.update'), [
        'name' => 'Test',
        'email' => 'test@example.com',
        'current_password' => 'password',
    ])->assertSessionHasNoErrors();

    expect($this->user->refresh()->email)->toBe('test@example.com');
});

test('password can be updated and the current session stays logged in', function () {
    $this->actingAs($this->user);

    $this->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    $this->user->refresh();
    expect(Hash::check('new-password', $this->user->password))->toBeTrue();
    expect($this->user->two_factor_secret)->toBeNull();
    expect($this->user->two_factor_recovery_codes)->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();

    $this->get(route('profile'))->assertSuccessful();
});

test('correct password must be provided to update password', function () {
    $this->actingAs($this->user);

    $this->put(route('user-password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasErrorsIn('updatePassword', 'current_password');
});

test('other sessions are logged out after the password changes', function () {
    $this->actingAs($this->user);

    $this->get(route('profile'))->assertSuccessful();

    $this->user->forceFill(['password' => Hash::make('changed-elsewhere')])->save();

    $this->get(route('profile'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('password reset keeps two factor and revokes api tokens', function () {
    vitoPestFeatureProfileTestEnableTwoFactor($this->user);
    $this->user->createToken('api-key', ['read', 'write']);
    $token = Password::broker()->createToken($this->user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    $this->user->refresh();
    expect(Hash::check('new-password', $this->user->password))->toBeTrue();
    expect($this->user->two_factor_secret)->not->toBeNull();
    expect($this->user->two_factor_confirmed_at)->not->toBeNull();
    expect($this->user->tokens()->count())->toBe(0);
});

test('two factor management requires password confirmation', function (string $method, string $uri) {
    vitoPestFeatureProfileTestEnableTwoFactor($this->user);
    $this->actingAs($this->user);

    $this->json($method, $uri)->assertStatus(423);
})->with([
    ['GET', '/user/two-factor-recovery-codes'],
    ['POST', '/user/two-factor-recovery-codes'],
    ['DELETE', '/user/two-factor-authentication'],
    ['POST', '/user/two-factor-authentication'],
    ['GET', '/user/two-factor-qr-code'],
]);

test('recovery codes are returned after password confirmation', function () {
    vitoPestFeatureProfileTestEnableTwoFactor($this->user);
    $this->actingAs($this->user);

    $this->postJson('/user/confirm-password', ['password' => 'password'])->assertCreated();

    $this->getJson('/user/two-factor-recovery-codes')
        ->assertSuccessful()
        ->assertExactJson(['recovery-code-1']);
});

test('two factor can be disabled after password confirmation', function () {
    vitoPestFeatureProfileTestEnableTwoFactor($this->user);
    $this->actingAs($this->user);

    $this->withSession(['auth.password_confirmed_at' => time()])
        ->delete('/user/two-factor-authentication')
        ->assertRedirect();

    expect($this->user->refresh()->two_factor_secret)->toBeNull();
});
