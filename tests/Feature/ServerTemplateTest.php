<?php

use App\Models\ServerTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->ensureHasDefaultProject();
});

test('index returns user server templates', function () {
    // Create some server templates for the user
    $templates = ServerTemplate::factory()->count(3)->create([
        'user_id' => $this->user->id,
    ]);

    // Create a template for another user (should not be returned)
    $otherUser = User::factory()->create();
    ServerTemplate::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('server-templates.index'));

    $response->assertStatus(200)
        ->assertJson([
            'templates' => $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'services' => $template->services,
                    'user_id' => $template->user_id,
                ];
            })->toArray(),
        ]);

    // Ensure only user's templates are returned
    expect($response->json('templates'))->toHaveCount(3);
});

test('index requires authentication', function () {
    $response = $this->getJson(route('server-templates.index'));

    $response->assertStatus(401);
});

test('store creates server template', function () {
    $data = [
        'name' => 'My Web Server Template',
        'services' => [
            'php' => '8.4',
            'nginx' => 'latest',
            'mysql' => '8.4',
        ],
    ];

    $response = $this->actingAs($this->user)
        ->post(route('server-templates.store'), $data);

    $response->assertRedirect()
        ->assertSessionHas('success', 'Server template created successfully.');

    $this->assertDatabaseHas('server_templates', [
        'user_id' => $this->user->id,
        'name' => 'My Web Server Template',
        'services' => $this->castAsJson([
            'php' => '8.4',
            'nginx' => 'latest',
            'mysql' => '8.4',
        ]),
    ]);
});

test('store validates required fields', function () {
    $response = $this->actingAs($this->user)
        ->post(route('server-templates.store'), []);

    $response->assertSessionHasErrors(['name', 'services']);
});

test('store validates name is string', function () {
    $data = [
        'name' => 123,
        'services' => ['php' => '8.4'],
    ];

    $response = $this->actingAs($this->user)
        ->post(route('server-templates.store'), $data);

    $response->assertSessionHasErrors(['name']);
});

test('store validates name max length', function () {
    $data = [
        'name' => str_repeat('a', 256), // 256 characters (over the 255 limit)
        'services' => ['php' => '8.4'],
    ];

    $response = $this->actingAs($this->user)
        ->post(route('server-templates.store'), $data);

    $response->assertSessionHasErrors(['name']);
});

test('store validates services is array', function () {
    $data = [
        'name' => 'Test Template',
        'services' => 'not-an-array',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('server-templates.store'), $data);

    $response->assertSessionHasErrors(['services']);
});

test('store requires authentication', function () {
    $data = [
        'name' => 'Test Template',
        'services' => ['php' => '8.4'],
    ];

    $response = $this->post(route('server-templates.store'), $data);

    $response->assertStatus(302);
    // Redirect to login
});

test('update modifies existing server template', function () {
    $template = ServerTemplate::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Original Name',
        'services' => ['php' => '8.2'],
    ]);

    $updateData = [
        'name' => 'Updated Name',
        'services' => [
            'php' => '8.4',
            'nginx' => 'latest',
        ],
    ];

    $response = $this->actingAs($this->user)
        ->put(route('server-templates.update', $template->id), $updateData);

    $response->assertRedirect()
        ->assertSessionHas('success', 'Server template updated successfully.');

    $template->refresh();
    expect($template->name)->toEqual('Updated Name');
    expect($template->services)->toEqual([
        'php' => '8.4',
        'nginx' => 'latest',
    ]);
});

test('update allows partial updates', function () {
    $template = ServerTemplate::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Original Name',
        'services' => ['php' => '8.2'],
    ]);

    // Update only the name
    $response = $this->actingAs($this->user)
        ->put(route('server-templates.update', $template->id), [
            'name' => 'New Name Only',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success', 'Server template updated successfully.');

    $template->refresh();
    expect($template->name)->toEqual('New Name Only');
    expect($template->services)->toEqual(['php' => '8.2']);

    // Should remain unchanged
    // Update only the services
    $response = $this->actingAs($this->user)
        ->put(route('server-templates.update', $template->id), [
            'services' => ['nginx' => 'latest'],
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success', 'Server template updated successfully.');

    $template->refresh();
    expect($template->name)->toEqual('New Name Only');
    // Should remain unchanged
    expect($template->services)->toEqual(['nginx' => 'latest']);
});

test('update validates fields when provided', function () {
    $template = ServerTemplate::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->put(route('server-templates.update', $template->id), [
            'name' => '', // Invalid: empty string
            'services' => 'not-an-array', // Invalid: not an array
        ]);

    $response->assertSessionHasErrors(['name', 'services']);
});

test('update prevents access to other users templates', function () {
    $otherUser = User::factory()->create();
    $template = ServerTemplate::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->put(route('server-templates.update', $template->id), [
            'name' => 'Hacked Name',
        ]);

    $response->assertStatus(404);
});

test('update returns 404 for nonexistent template', function () {
    $response = $this->actingAs($this->user)
        ->put(route('server-templates.update', 99999), [
            'name' => 'Test Name',
        ]);

    $response->assertStatus(404);
});

test('update requires authentication', function () {
    $template = ServerTemplate::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->put(route('server-templates.update', $template->id), [
        'name' => 'Test Name',
    ]);

    $response->assertStatus(302);
    // Redirect to login
});

test('destroy deletes server template', function () {
    $template = ServerTemplate::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('server-templates.destroy', $template->id));

    $response->assertRedirect()
        ->assertSessionHas('success', 'Server template deleted successfully.');

    $this->assertDatabaseMissing('server_templates', [
        'id' => $template->id,
    ]);
});

test('destroy prevents access to other users templates', function () {
    $otherUser = User::factory()->create();
    $template = ServerTemplate::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('server-templates.destroy', $template->id));

    $response->assertStatus(404);

    // Ensure the template still exists
    $this->assertDatabaseHas('server_templates', [
        'id' => $template->id,
    ]);
});

test('destroy returns 404 for nonexistent template', function () {
    $response = $this->actingAs($this->user)
        ->delete(route('server-templates.destroy', 99999));

    $response->assertStatus(404);
});

test('destroy requires authentication', function () {
    $template = ServerTemplate::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->delete(route('server-templates.destroy', $template->id));

    $response->assertStatus(302);
    // Redirect to login
});

test('server template belongs to user', function () {
    $template = ServerTemplate::factory()->create([
        'user_id' => $this->user->id,
    ]);

    expect($template->user->id)->toEqual($this->user->id);
    expect($template->user)->toBeInstanceOf(User::class);
});

test('user has many server templates', function () {
    $templates = ServerTemplate::factory()->count(3)->create([
        'user_id' => $this->user->id,
    ]);

    $userTemplates = $this->user->serverTemplates()->get();

    expect($userTemplates)->toHaveCount(3);
    expect($userTemplates->pluck('id')->sort()->values())->toEqual($templates->pluck('id')->sort()->values());
});

test('server template casts services to array', function () {
    $services = [
        'php' => '8.4',
        'nginx' => 'latest',
        'mysql' => '8.4',
    ];

    $template = ServerTemplate::factory()->create([
        'user_id' => $this->user->id,
        'services' => $services,
    ]);

    expect($template->services)->toBeArray();
    expect($template->services)->toEqual($services);

    // Test with fresh instance from database
    $template = ServerTemplate::find($template->id);
    expect($template->services)->toBeArray();
    expect($template->services)->toEqual($services);
});

test('server template factory creates valid instance', function () {
    $template = ServerTemplate::factory()->create([
        'user_id' => $this->user->id,
    ]);

    expect($template)->toBeInstanceOf(ServerTemplate::class);
    expect($template->user_id)->toEqual($this->user->id);
    expect($template->name)->toBeString();
    expect($template->services)->toBeArray();
    expect($template->name)->not->toBeEmpty();
    expect($template->services)->not->toBeEmpty();
});
