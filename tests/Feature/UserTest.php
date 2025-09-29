<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user(): void
    {
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
    }

    public function test_see_users_list(): void
    {
        $this->actingAs($this->user);

        User::factory()->create();

        $this->get(route('users'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('users/index'));
    }

    public function test_must_be_admin_to_see_users_list(): void
    {
        $this->user->is_admin = false;
        $this->user->save();

        $this->actingAs($this->user);

        $this->get(route('users'))
            ->assertNotFound();
    }

    public function test_delete_user(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();

        $this->delete(route('users.destroy', $user))
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('users'));

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_cannot_delete_yourself(): void
    {
        $this->actingAs($this->user);

        $this->delete(route('users.destroy', $this->user))
            ->assertForbidden();
    }

    public function test_edit_user_info(): void
    {
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
    }
}
