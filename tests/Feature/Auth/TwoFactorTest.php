<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function test_user_can_enable_two_factor_authentication(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->ensureHasDefaultProject();

        $this->actingAs($user);

        // Enable two-factor authentication
        $this->post(route('two-factor.enable'))
            ->assertSessionDoesntHaveErrors();

        $user = $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        // Generate a valid TOTP code from the secret
        $google2fa = new Google2FA;
        $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        // Submit the code to confirm 2FA
        $this->post(route('two-factor.confirm'), [
            'code' => $validCode,
        ])->assertSessionDoesntHaveErrors();

        // Assert the user is now confirmed
        $this->assertNotNull($user->refresh()->two_factor_confirmed_at);
    }

    public function test_user_can_disable_two_factor_authentication(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->ensureHasDefaultProject();

        $this->actingAs($user);

        // First, enable 2FA
        $this->post(route('two-factor.enable'))
            ->assertSessionDoesntHaveErrors();

        $user = $user->refresh();

        // Ensure 2FA secret is set
        $this->assertNotNull($user->two_factor_secret);

        // Now disable 2FA
        $this->delete(route('two-factor.disable'))
            ->assertSessionDoesntHaveErrors();

        $user = $user->refresh();

        // Ensure 2FA is fully removed
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertEmpty($user->two_factor_recovery_codes ?? []);
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function test_see_two_factor_challenge(): void
    {
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
        $this->assertNotNull($loginId);

        $user->refresh();
        $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));

        $response = $this->withSession([
            'login.id' => $loginId,
        ])->post(route('two-factor.login.store'), [
            'code' => $code,
        ])
            ->assertSessionDoesntHaveErrors();

        $response->assertRedirect(route('servers')); // or your expected redirect route
        $this->assertAuthenticatedAs($user);
    }
}
