<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\ProjectInvitation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProjectsUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_invite_others(): void
    {
        Mail::fake();

        $this->actingAs($this->user);

        // make sure the user has default project
        $project = $this->user->ensureHasDefaultProject();

        $this
            ->from(route('projects'))
            ->post(route('projects.users.store', ['project' => $project]), [
                'email' => 'new-user@example.com',
                'role' => UserRole::ADMIN->value,
            ])
            ->assertRedirect(route('projects'))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('user_project', [
            'project_id' => $project->id,
            'email' => 'new-user@example.com',
        ]);

        Mail::assertSent(ProjectInvitation::class);
    }

    public function test_can_remove_registered_user_from_project(): void
    {
        $this->actingAs($this->user);

        // make sure the user has default project
        $project = $this->user->ensureHasDefaultProject();

        /** @var User $newUser */
        $newUser = User::factory()->create();

        $userProject = $project->users()->create([
            'project_id' => $project->id,
            'user_id' => $newUser->id,
            'role' => UserRole::USER,
        ]);

        $this
            ->from(route('projects'))
            ->delete(route('projects.users.destroy', ['project' => $project, 'id' => $userProject->id]))
            ->assertRedirect(route('projects'))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('user_project', [
            'project_id' => $project->id,
            'user_id' => $newUser->id,
        ]);
    }

    public function test_can_remove_owner_from_project(): void
    {
        $this->actingAs($this->user);

        // make sure the user has default project
        $project = $this->user->ensureHasDefaultProject();

        $id = $project->users()->where('user_id', $this->user->id)->first()->id;

        $this
            ->from(route('projects'))
            ->delete(route('projects.users.destroy', ['project' => $project, 'id' => $id]))
            ->assertSessionHas([
                'error' => __('You cannot remove the project owner.'),
            ]);
    }

    public function test_can_remove_invited_user_from_project(): void
    {
        $this->actingAs($this->user);

        // make sure the user has default project
        $project = $this->user->ensureHasDefaultProject();

        $userProject = $project->users()->create([
            'project_id' => $project->id,
            'email' => 'new-user@example.com',
            'role' => UserRole::USER,
        ]);

        $this
            ->from(route('projects'))
            ->delete(route('projects.users.destroy', ['project' => $project, 'id' => $userProject->id]))
            ->assertRedirect(route('projects'))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('user_project', [
            'project_id' => $project->id,
            'email' => 'new-user@example.com',
        ]);
    }

    public function test_user_can_accept_invitation(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();
        $ownerProject = $owner->ensureHasDefaultProject();

        $this->actingAs($this->user);

        $ownerProject->users()->create([
            'email' => $this->user->email,
            'role' => UserRole::USER,
        ]);

        $this
            ->from(route('projects'))
            ->get(route('projects.invitations.accept', ['project' => $ownerProject]))
            ->assertRedirect(route('projects'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('user_project', [
            'project_id' => $ownerProject->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_cannot_join_without_invitation(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();
        $ownerProject = $owner->ensureHasDefaultProject();

        $this->actingAs($this->user);

        $this
            ->from(route('projects'))
            ->get(route('projects.invitations.accept', ['project' => $ownerProject]))
            ->assertNotFound();

        $this->assertDatabaseMissing('user_project', [
            'project_id' => $ownerProject->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_leave_project(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();
        $ownerProject = $owner->ensureHasDefaultProject();

        $this->actingAs($this->user);

        $ownerProject->users()->create([
            'email' => $this->user->email,
            'role' => UserRole::USER,
        ]);

        $this
            ->from(route('projects'))
            ->delete(route('projects.leave', ['project' => $ownerProject]))
            ->assertRedirect(route('projects'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('user_project', [
            'project_id' => $ownerProject->id,
            'email' => $this->user->email,
        ]);
    }

    public function test_user_can_leave_project_that_is_not_invited(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();
        $ownerProject = $owner->ensureHasDefaultProject();

        $this->actingAs($this->user);

        $this
            ->from(route('projects'))
            ->delete(route('projects.leave', ['project' => $ownerProject]))
            ->assertNotFound();
    }

    public function test_cannot_delete_yourself_from_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $userProject = $project->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $this->delete(route('projects.users.destroy', ['project' => $project->id, 'id' => $userProject->id]))
            ->assertSessionHas([
                'error' => 'You cannot remove yourself from the project.',
            ]);
    }

    public function test_cannot_delete_the_owner(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $userProject = $project->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::OWNER,
        ]);

        $this->delete(route('projects.users.destroy', ['project' => $project->id, 'id' => $userProject->id]))
            ->assertSessionHas([
                'error' => 'You cannot remove the project owner.',
            ]);
    }
}
