<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
            'role' => UserRole::USER,
        ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('users'));

        $this->assertDatabaseHas('users', [
            'name' => 'new user',
            'email' => 'newuser@example.com',
            'role' => UserRole::USER,
        ]);
    }

    public function test_see_users_list(): void
    {
        $this->actingAs($this->user);

        User::factory()->create();

        $this->get(route('users'))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page->component('users/index'));
    }

    public function test_must_be_admin_to_see_users_list(): void
    {
        $this->user->role = UserRole::USER;
        $this->user->save();

        $this->actingAs($this->user);

        $this->get(route('users'))
            ->assertForbidden();
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
            'role' => UserRole::ADMIN,
        ])
            ->assertRedirect(route('users'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'new-name',
            'email' => 'newemail@example.com',
            'role' => UserRole::ADMIN,
        ]);
    }

    public function test_add_user_to_project(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->post(route('users.projects.store', $user), [
            'project' => $project->id,
        ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('users'));

        $this->assertDatabaseHas('user_project', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_remove_user_from_project(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();
        $project = Project::factory()->create();

        $user->projects()->attach($project);

        $this->delete(route('users.projects.destroy', [$user, $project]))
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('users'));

        $this->assertDatabaseMissing('user_project', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }
}
