<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DesktopAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_login_is_unavailable_outside_desktop_mode(): void
    {
        config()->set('desktop.enabled', false);

        $this->get(route('desktop.login'))->assertNotFound();
    }

    public function test_desktop_login_screen_lists_users(): void
    {
        config()->set('desktop.enabled', true);

        $this->withoutVite();

        $this->get(route('desktop.login'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/desktop-login')
                ->where('setup_required', false)
                ->where('locked', false)
                ->where('locked_user_id', null)
                ->has('users', 1)
                ->where('users.0.id', $this->user->id)
            );
    }

    public function test_desktop_first_run_setup_screen_is_shown_when_no_users_exist(): void
    {
        config()->set('desktop.enabled', true);

        User::query()->delete();

        $this->withoutVite();

        $this->get(route('desktop.login'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/desktop-login')
                ->where('setup_required', true)
                ->where('locked', false)
                ->where('locked_user_id', null)
                ->has('users', 0)
            );
    }

    public function test_desktop_first_run_setup_creates_admin_and_logs_in(): void
    {
        config()->set('desktop.enabled', true);

        User::query()->delete();

        $response = $this->post(route('desktop.setup'), [
            'name' => 'Desktop Admin',
            'email' => 'desktop@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        /** @var User $user */
        $user = User::query()->where('email', 'desktop@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->is_admin);
        $this->assertSame('UTC', $user->timezone);
        $this->assertNotNull($user->current_project_id);
        $this->assertDatabaseHas('user_project', [
            'user_id' => $user->id,
            'project_id' => $user->current_project_id,
        ]);
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_desktop_first_run_setup_is_unavailable_when_user_exists(): void
    {
        config()->set('desktop.enabled', true);

        $this->post(route('desktop.setup'), [
            'name' => 'Second Admin',
            'email' => 'second@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', [
            'email' => 'second@example.com',
        ]);
    }

    public function test_desktop_first_run_setup_is_unavailable_outside_desktop_mode(): void
    {
        config()->set('desktop.enabled', false);

        User::query()->delete();

        $this->post(route('desktop.setup'), [
            'name' => 'Desktop Admin',
            'email' => 'desktop@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', [
            'email' => 'desktop@example.com',
        ]);
    }

    public function test_desktop_users_can_login_by_clicking_user_before_locking(): void
    {
        config()->set('desktop.enabled', true);

        $response = $this->post(route('desktop.login.store', $this->user));

        $this->assertAuthenticatedAs($this->user);
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_desktop_lock_logs_out_and_marks_session_locked(): void
    {
        config()->set('desktop.enabled', true);

        $response = $this->actingAs($this->user)->post(route('desktop.lock'));

        $this->assertGuest();
        $response
            ->assertRedirect(route('desktop.login'))
            ->assertSessionHas('desktop.locked_user_id', $this->user->id);
    }

    public function test_desktop_unlock_requires_password_after_locking(): void
    {
        config()->set('desktop.enabled', true);

        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('secret-password'),
        ]);

        $this->withSession(['desktop.locked_user_id' => $user->id])
            ->post(route('desktop.login.store', $user), [
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertGuest();

        $response = $this->withSession(['desktop.locked_user_id' => $user->id])
            ->post(route('desktop.login.store', $user), [
                'password' => 'secret-password',
            ]);

        $this->assertAuthenticatedAs($user);
        $response
            ->assertRedirect(RouteServiceProvider::HOME)
            ->assertSessionMissing('desktop.locked_user_id');
    }
}
