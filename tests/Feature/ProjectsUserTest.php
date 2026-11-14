<?php

use App\Actions\Projects\InviteToProject;
use App\Enums\UserRole;
use App\Mail\ProjectInvitation;
use App\Models\Project;
use App\Models\User;
use App\Models\UserProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;

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

test('invitation stores a hashed token and mails a tokenized link', function () {
    Mail::fake();

    $this->actingAs($this->user);

    $project = $this->user->ensureHasDefaultProject();

    $this->post(route('projects.users.store', ['project' => $project]), [
        'email' => 'new-user@example.com',
        'role' => UserRole::USER->value,
    ])->assertSessionDoesntHaveErrors();

    $invitation = UserProject::query()->where('email', 'new-user@example.com')->firstOrFail();
    expect($invitation->invitation_token)->toHaveLength(64);

    Mail::assertSent(ProjectInvitation::class, function (ProjectInvitation $mail) use ($invitation, $project) {
        preg_match('#/settings/projects/'.$project->id.'/invitations/([A-Za-z0-9]{64})#', $mail->render(), $matches);

        return isset($matches[1]) && InviteToProject::hashToken($matches[1]) === $invitation->invitation_token;
    });
});

test('user can accept invitation with token', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $ownerProject->users()->create([
        'email' => $this->user->email,
        'role' => UserRole::USER,
    ])->forceFill(['invitation_token' => InviteToProject::hashToken('secret-token')])->save();

    $this->get(route('projects.invitations.show', ['project' => $ownerProject, 'token' => 'secret-token']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('projects/accept-invitation'));

    $this->assertDatabaseMissing('user_project', [
        'project_id' => $ownerProject->id,
        'user_id' => $this->user->id,
    ]);

    $this
        ->post(route('projects.invitations.accept', ['project' => $ownerProject, 'token' => 'secret-token']))
        ->assertRedirect(route('projects'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('user_project', [
        'project_id' => $ownerProject->id,
        'user_id' => $this->user->id,
        'email' => null,
        'invitation_token' => null,
    ]);
});

test('user cannot accept invitation without the token', function (string $token) {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $ownerProject->users()->create([
        'email' => $this->user->email,
        'role' => UserRole::ADMIN,
    ])->forceFill(['invitation_token' => InviteToProject::hashToken('secret-token')])->save();

    $this->get(route('projects.invitations.show', ['project' => $ownerProject, 'token' => $token]))->assertNotFound();
    $this->post(route('projects.invitations.accept', ['project' => $ownerProject, 'token' => $token]))->assertSessionHasErrors('invitation');
    $this->delete(route('projects.invitations.decline', ['project' => $ownerProject, 'token' => $token]))->assertSessionHasErrors('invitation');

    $this->assertDatabaseHas('user_project', [
        'project_id' => $ownerProject->id,
        'email' => $this->user->email,
    ]);

    $this->assertDatabaseMissing('user_project', [
        'project_id' => $ownerProject->id,
        'user_id' => $this->user->id,
    ]);
})->with(['wrong token' => ['wrong-token'], 'hashed token' => [InviteToProject::hashToken('secret-token')]]);

test('user cannot accept an invitation sent to another email', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $ownerProject->users()->create([
        'email' => 'someone-else@example.com',
        'role' => UserRole::ADMIN,
    ])->forceFill(['invitation_token' => InviteToProject::hashToken('secret-token')])->save();

    $this->get(route('projects.invitations.show', ['project' => $ownerProject, 'token' => 'secret-token']))->assertNotFound();
    $this->post(route('projects.invitations.accept', ['project' => $ownerProject, 'token' => 'secret-token']))->assertSessionHasErrors('invitation');
});

test('legacy invitations without a token cannot be accepted', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $ownerProject->users()->create([
        'email' => $this->user->email,
        'role' => UserRole::USER,
    ]);

    $this->post(route('projects.invitations.accept', ['project' => $ownerProject, 'token' => 'anything']))->assertSessionHasErrors('invitation');

    $this->assertDatabaseMissing('user_project', [
        'project_id' => $ownerProject->id,
        'user_id' => $this->user->id,
    ]);
});

test('user can decline an invitation with the token', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $ownerProject->users()->create([
        'email' => $this->user->email,
        'role' => UserRole::USER,
    ])->forceFill(['invitation_token' => InviteToProject::hashToken('secret-token')])->save();

    $this->delete(route('projects.invitations.decline', ['project' => $ownerProject, 'token' => 'secret-token']))
        ->assertRedirect(route('projects'));

    $this->assertDatabaseMissing('user_project', [
        'project_id' => $ownerProject->id,
        'email' => $this->user->email,
    ]);
});

test('an email-matched invitation cannot be declined through leave', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $ownerProject->users()->create([
        'email' => $this->user->email,
        'role' => UserRole::USER,
    ]);

    $this->delete(route('projects.leave', ['project' => $ownerProject]))->assertNotFound();

    $this->assertDatabaseHas('user_project', [
        'project_id' => $ownerProject->id,
        'email' => $this->user->email,
    ]);
});

test('accepting an invitation as an existing member removes the invitation', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();
    $ownerProject->users()->create(['user_id' => $this->user->id, 'role' => UserRole::USER]);
    $ownerProject->users()->create([
        'email' => $this->user->email,
        'role' => UserRole::ADMIN,
    ])->forceFill(['invitation_token' => InviteToProject::hashToken('secret-token')])->save();

    $this->actingAs($this->user);

    $this->post(route('projects.invitations.accept', ['project' => $ownerProject, 'token' => 'secret-token']))
        ->assertRedirect(route('projects'));

    expect($ownerProject->users()->where('user_id', $this->user->id)->count())->toBe(1)
        ->and($ownerProject->role($this->user))->toBe(UserRole::USER)
        ->and($ownerProject->users()->where('email', $this->user->email)->exists())->toBeFalse();
});

test('invitation is rolled back when the email cannot be sent', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('mail down'));

    $this->actingAs($this->user);

    $project = $this->user->ensureHasDefaultProject();

    $this->post(route('projects.users.store', ['project' => $project]), [
        'email' => 'new-user@example.com',
        'role' => UserRole::USER->value,
    ])->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('user_project', ['email' => 'new-user@example.com']);
});

test('an accepted invitation cannot be declined afterwards', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();
    $ownerProject->users()->create([
        'email' => $this->user->email,
        'role' => UserRole::USER,
    ])->forceFill(['invitation_token' => InviteToProject::hashToken('secret-token')])->save();

    $this->actingAs($this->user);

    $this->post(route('projects.invitations.accept', ['project' => $ownerProject, 'token' => 'secret-token']));
    $this->delete(route('projects.invitations.decline', ['project' => $ownerProject, 'token' => 'secret-token']))
        ->assertSessionHasErrors('invitation');

    $this->assertDatabaseHas('user_project', ['project_id' => $ownerProject->id, 'user_id' => $this->user->id]);
});

test('leaving a project does not remove invitations in other projects', function () {
    $project = $this->user->ensureHasDefaultProject();
    $otherProject = Project::factory()->create();
    $otherProject->users()->create([
        'email' => $this->user->email,
        'role' => UserRole::USER,
    ]);

    $this->actingAs($this->user);

    $this->delete(route('projects.leave', ['project' => $project]))->assertSessionHas('success');

    $this->assertDatabaseHas('user_project', [
        'project_id' => $otherProject->id,
        'email' => $this->user->email,
    ]);
});

test('user can leave project', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $ownerProject->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::USER,
    ]);

    $this
        ->from(route('projects'))
        ->delete(route('projects.leave', ['project' => $ownerProject]))
        ->assertRedirect(route('projects'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('user_project', [
        'project_id' => $ownerProject->id,
        'user_id' => $this->user->id,
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
