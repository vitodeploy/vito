<?php

use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see workflows list', function () {
    $this->actingAs($this->user);

    Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $this->get(route('workflows'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('workflows/index'));
});

test('see workflow', function () {
    $this->actingAs($this->user);

    /** @var Workflow $workflow */
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $this->get(route('workflows.show', $workflow))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('workflows/show'));
});

test('create workflow', function () {
    $this->actingAs($this->user);

    $this->post(route('workflows.store'), [
        'name' => 'My Workflow',
    ])->assertRedirect();

    $this->assertDatabaseHas('workflows', [
        'project_id' => $this->user->current_project_id,
        'name' => 'My Workflow',
    ]);
});

test('delete workflow', function () {
    $this->actingAs($this->user);

    /** @var Workflow $workflow */
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $this->delete(route('workflows.destroy', $workflow))
        ->assertRedirect();

    $this->assertSoftDeleted('workflows', [
        'id' => $workflow->id,
    ]);
});
