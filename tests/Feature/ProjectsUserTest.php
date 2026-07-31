<?php

use App\Enums\UserRole;
use App\Mail\ProjectInvitation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('user can invite others', function () {
    Mail::fake();

    $this->actingAs($this->user);

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
});

test('can remove registered user from project', function () {
    $this->actingAs($this->user);

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
});

test('can remove owner from project', function () {
    $this->actingAs($this->user);

    $project = $this->user->ensureHasDefaultProject();

    $id = $project->users()->where('user_id', $this->user->id)->first()->id;

    $this
        ->from(route('projects'))
        ->delete(route('projects.users.destroy', ['project' => $project, 'id' => $id]))
        ->assertSessionHas([
            'error' => __('You cannot remove the project owner.'),
        ]);
});

test('can remove invited user from project', function () {
    $this->actingAs($this->user);

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
});

test('user can accept invitation', function () {
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
});

test('user cannot join without invitation', function () {
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
});

test('user can leave project', function () {
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
});

test('user can leave project that is not invited', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $this
        ->from(route('projects'))
        ->delete(route('projects.leave', ['project' => $ownerProject]))
        ->assertNotFound();
});

test('cannot delete yourself from project', function () {
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
});

test('cannot delete the owner', function () {
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
});
