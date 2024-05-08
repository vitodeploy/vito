<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user(): void
    {
        $this->actingAs($this->user);

        $this->post(route('settings.users.store'), [
            'name' => 'new user',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'role' => UserRole::USER,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'new user',
            'email' => 'newuser@example.com',
            'role' => UserRole::USER,
        ]);
    }

    public function test_see_users_list(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();

        $this->get(route('settings.users.index'))
            ->assertSuccessful()
            ->assertSee($user->name);
    }

    public function test_must_be_admin_to_see_users_list(): void
    {
        $this->user->role = UserRole::USER;
        $this->user->save();

        $this->actingAs($this->user);

        $user = User::factory()->create();

        $this->get(route('settings.users.index'))
            ->assertForbidden();
    }

    public function test_delete_user(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();

        $this->delete(route('settings.users.delete', $user))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_cannot_delete_yourself(): void
    {
        $this->actingAs($this->user);

        $this->delete(route('settings.users.delete', $this->user))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('toast.type', 'error');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
        ]);
    }

    public function test_see_user(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();

        $this->get(route('settings.users.show', $user))
            ->assertSuccessful()
            ->assertSee($user->name);
    }

    public function test_edit_user_info(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();

        $this->post(route('settings.users.update', $user), [
            'name' => 'new-name',
            'email' => 'newemail@example.com',
            'timezone' => 'Europe/London',
            'role' => UserRole::ADMIN,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'new-name',
            'email' => 'newemail@example.com',
            'timezone' => 'Europe/London',
            'role' => UserRole::ADMIN,
        ]);
    }

    public function test_edit_user_projects(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->post(route('settings.users.update-projects', $user), [
            'projects' => [$project->id],
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('user_project', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_edit_user_projects_with_current_project(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();
        $user->current_project_id = null;
        $user->save();

        $project = Project::factory()->create();

        $this->post(route('settings.users.update-projects', $user), [
            'projects' => [$project->id],
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('user_project', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_project_id' => $project->id,
        ]);
    }
}
