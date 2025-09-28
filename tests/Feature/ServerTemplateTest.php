<?php

namespace Tests\Feature;

use App\Models\ServerTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->ensureHasDefaultProject();
    }

    public function test_index_returns_user_server_templates(): void
    {
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
        $this->assertCount(3, $response->json('templates'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson(route('server-templates.index'));

        $response->assertStatus(401);
    }

    public function test_store_creates_server_template(): void
    {
        $data = [
            'name' => 'My Web Server Template',
            'services' => [
                'php' => '8.4',
                'nginx' => 'latest',
                'mysql' => '8.0',
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
                'mysql' => '8.0',
            ]),
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('server-templates.store'), []);

        $response->assertSessionHasErrors(['name', 'services']);
    }

    public function test_store_validates_name_is_string(): void
    {
        $data = [
            'name' => 123,
            'services' => ['php' => '8.4'],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('server-templates.store'), $data);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_name_max_length(): void
    {
        $data = [
            'name' => str_repeat('a', 256), // 256 characters (over the 255 limit)
            'services' => ['php' => '8.4'],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('server-templates.store'), $data);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_services_is_array(): void
    {
        $data = [
            'name' => 'Test Template',
            'services' => 'not-an-array',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('server-templates.store'), $data);

        $response->assertSessionHasErrors(['services']);
    }

    public function test_store_requires_authentication(): void
    {
        $data = [
            'name' => 'Test Template',
            'services' => ['php' => '8.4'],
        ];

        $response = $this->post(route('server-templates.store'), $data);

        $response->assertStatus(302); // Redirect to login
    }

    public function test_update_modifies_existing_server_template(): void
    {
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
        $this->assertEquals('Updated Name', $template->name);
        $this->assertEquals([
            'php' => '8.4',
            'nginx' => 'latest',
        ], $template->services);
    }

    public function test_update_allows_partial_updates(): void
    {
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
        $this->assertEquals('New Name Only', $template->name);
        $this->assertEquals(['php' => '8.2'], $template->services); // Should remain unchanged

        // Update only the services
        $response = $this->actingAs($this->user)
            ->put(route('server-templates.update', $template->id), [
                'services' => ['nginx' => 'latest'],
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Server template updated successfully.');

        $template->refresh();
        $this->assertEquals('New Name Only', $template->name); // Should remain unchanged
        $this->assertEquals(['nginx' => 'latest'], $template->services);
    }

    public function test_update_validates_fields_when_provided(): void
    {
        $template = ServerTemplate::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('server-templates.update', $template->id), [
                'name' => '', // Invalid: empty string
                'services' => 'not-an-array', // Invalid: not an array
            ]);

        $response->assertSessionHasErrors(['name', 'services']);
    }

    public function test_update_prevents_access_to_other_users_templates(): void
    {
        $otherUser = User::factory()->create();
        $template = ServerTemplate::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('server-templates.update', $template->id), [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(404);
    }

    public function test_update_returns_404_for_nonexistent_template(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('server-templates.update', 99999), [
                'name' => 'Test Name',
            ]);

        $response->assertStatus(404);
    }

    public function test_update_requires_authentication(): void
    {
        $template = ServerTemplate::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->put(route('server-templates.update', $template->id), [
            'name' => 'Test Name',
        ]);

        $response->assertStatus(302); // Redirect to login
    }

    public function test_destroy_deletes_server_template(): void
    {
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
    }

    public function test_destroy_prevents_access_to_other_users_templates(): void
    {
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
    }

    public function test_destroy_returns_404_for_nonexistent_template(): void
    {
        $response = $this->actingAs($this->user)
            ->delete(route('server-templates.destroy', 99999));

        $response->assertStatus(404);
    }

    public function test_destroy_requires_authentication(): void
    {
        $template = ServerTemplate::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->delete(route('server-templates.destroy', $template->id));

        $response->assertStatus(302); // Redirect to login
    }

    public function test_server_template_belongs_to_user(): void
    {
        $template = ServerTemplate::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals($this->user->id, $template->user->id);
        $this->assertInstanceOf(User::class, $template->user);
    }

    public function test_user_has_many_server_templates(): void
    {
        $templates = ServerTemplate::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $userTemplates = $this->user->serverTemplates()->get();

        $this->assertCount(3, $userTemplates);
        $this->assertEquals($templates->pluck('id')->sort()->values(), $userTemplates->pluck('id')->sort()->values());
    }

    public function test_server_template_casts_services_to_array(): void
    {
        $services = [
            'php' => '8.4',
            'nginx' => 'latest',
            'mysql' => '8.0',
        ];

        $template = ServerTemplate::factory()->create([
            'user_id' => $this->user->id,
            'services' => $services,
        ]);

        $this->assertIsArray($template->services);
        $this->assertEquals($services, $template->services);

        // Test with fresh instance from database
        $template = ServerTemplate::find($template->id);
        $this->assertIsArray($template->services);
        $this->assertEquals($services, $template->services);
    }

    public function test_server_template_factory_creates_valid_instance(): void
    {
        $template = ServerTemplate::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(ServerTemplate::class, $template);
        $this->assertEquals($this->user->id, $template->user_id);
        $this->assertIsString($template->name);
        $this->assertIsArray($template->services);
        $this->assertNotEmpty($template->name);
        $this->assertNotEmpty($template->services);
    }
}
