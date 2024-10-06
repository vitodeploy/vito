<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Web\Pages\Settings\Users\Index;
use App\Web\Pages\Settings\Users\Widgets\UsersList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Index::class)
            ->callAction('create', [
                'name' => 'new user',
                'email' => 'newuser@example.com',
                'password' => 'password',
                'role' => UserRole::USER,
            ]);

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

        $this->get(Index::getUrl())
            ->assertSuccessful();

        Livewire::test(UsersList::class)
            ->assertCanSeeTableRecords([$user]);
    }

    public function test_must_be_admin_to_see_users_list(): void
    {
        $this->user->role = UserRole::USER;
        $this->user->save();

        $this->actingAs($this->user);

        $this->get(Index::getUrl())
            ->assertForbidden();
    }

    public function test_delete_user(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();

        Livewire::test(UsersList::class)
            ->callTableAction('delete', $user);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_cannot_delete_yourself(): void
    {
        $this->actingAs($this->user);

        Livewire::test(UsersList::class)
            ->assertTableActionHidden('delete', $this->user);
    }

    public function test_edit_user_info(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create();

        Livewire::test(UsersList::class)
            ->callTableAction('edit', $user, [
                'name' => 'new-name',
                'email' => 'newemail@example.com',
                'timezone' => 'Europe/London',
                'role' => UserRole::ADMIN,
            ])
            ->assertSuccessful();

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

        Livewire::test(UsersList::class)
            ->callTableAction('update-projects', $user, [
                'projects' => [$project->id],
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('user_project', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_edit_user_projects_with_current_project(): void
    {
        $this->actingAs($this->user);

        /** @var User $user */
        $user = User::factory()->create();
        $user->current_project_id = null;
        $user->save();

        /** @var Project $project */
        $project = Project::factory()->create();

        Livewire::test(UsersList::class)
            ->callTableAction('update-projects', $user, [
                'projects' => [$project->id],
            ])
            ->assertSuccessful();

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
