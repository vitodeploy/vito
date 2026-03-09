<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_api_key_with_read_permission(): void
    {
        $this->actingAs($this->user)
            ->post(route('api-keys.store'), [
                'name' => 'test-key',
                'permission' => 'read',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'test-key',
            'tokenable_id' => $this->user->id,
        ]);
    }

    public function test_create_api_key_with_write_permission(): void
    {
        $this->actingAs($this->user)
            ->post(route('api-keys.store'), [
                'name' => 'test-key',
                'permission' => 'write',
            ])
            ->assertSessionHas('success');

        $token = $this->user->tokens()->where('name', 'test-key')->first();
        $this->assertContains('read', $token->abilities);
        $this->assertContains('write', $token->abilities);
    }

    public function test_create_api_key_with_project_scope(): void
    {
        $this->actingAs($this->user)
            ->post(route('api-keys.store'), [
                'name' => 'scoped-key',
                'permission' => 'read',
                'projects' => [$this->user->current_project_id],
            ])
            ->assertSessionHas('success');

        $token = $this->user->tokens()->where('name', 'scoped-key')->first();
        $this->assertContains('read', $token->abilities);
        $this->assertContains('project:'.$this->user->current_project_id, $token->abilities);
    }

    public function test_create_api_key_without_project_scope(): void
    {
        $this->actingAs($this->user)
            ->post(route('api-keys.store'), [
                'name' => 'full-access-key',
                'permission' => 'write',
            ])
            ->assertSessionHas('success');

        $token = $this->user->tokens()->where('name', 'full-access-key')->first();
        $this->assertContains('read', $token->abilities);
        $this->assertContains('write', $token->abilities);
        $this->assertEmpty(
            collect($token->abilities)->filter(fn ($a) => str_starts_with($a, 'project:'))->all()
        );
    }

    public function test_create_api_key_with_multiple_projects(): void
    {
        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $this->actingAs($this->user)
            ->post(route('api-keys.store'), [
                'name' => 'multi-project-key',
                'permission' => 'write',
                'projects' => [$this->user->current_project_id, $project2->id],
            ])
            ->assertSessionHas('success');

        $token = $this->user->tokens()->where('name', 'multi-project-key')->first();
        $this->assertContains('project:'.$this->user->current_project_id, $token->abilities);
        $this->assertContains('project:'.$project2->id, $token->abilities);
    }

    public function test_cannot_create_api_key_with_invalid_project(): void
    {
        $this->actingAs($this->user)
            ->post(route('api-keys.store'), [
                'name' => 'bad-key',
                'permission' => 'read',
                'projects' => [999999],
            ])
            ->assertSessionHasErrors('projects.0');
    }

    public function test_cannot_create_api_key_with_project_user_does_not_belong_to(): void
    {
        /** @var Project $otherProject */
        $otherProject = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('api-keys.store'), [
                'name' => 'bad-key',
                'permission' => 'read',
                'projects' => [$otherProject->id],
            ])
            ->assertSessionHasErrors('projects.0');
    }

    public function test_delete_api_key(): void
    {
        $token = $this->user->createToken('delete-me', ['read']);

        $this->actingAs($this->user)
            ->delete(route('api-keys.destroy', $token->accessToken->id))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_index_returns_api_keys(): void
    {
        $this->user->createToken('test-key', ['read', 'project:'.$this->user->current_project_id]);

        $this->actingAs($this->user)
            ->get(route('api-keys'))
            ->assertSuccessful();
    }

    public function test_index_returns_project_ids_and_filtered_permissions(): void
    {
        $this->user->createToken('scoped-key', ['read', 'write', 'project:'.$this->user->current_project_id]);

        $response = $this->actingAs($this->user)
            ->get(route('api-keys'))
            ->assertSuccessful();

        $apiKeys = $response->original->getData()['page']['props']['apiKeys']['data'];
        $key = collect($apiKeys)->firstWhere('name', 'scoped-key');

        $this->assertNotNull($key);
        $this->assertEquals(['read', 'write'], $key['permissions']);
        $this->assertEquals([$this->user->current_project_id], $key['project_ids']);
    }

    public function test_create_api_key_with_empty_projects_array(): void
    {
        $this->actingAs($this->user)
            ->post(route('api-keys.store'), [
                'name' => 'empty-projects-key',
                'permission' => 'read',
                'projects' => [],
            ])
            ->assertSessionHas('success');

        $token = $this->user->tokens()->where('name', 'empty-projects-key')->first();
        $this->assertContains('read', $token->abilities);
        $this->assertEmpty(
            collect($token->abilities)->filter(fn ($a) => str_starts_with($a, 'project:'))->all()
        );
    }

    public function test_cannot_create_api_key_with_invalid_permission(): void
    {
        $this->actingAs($this->user)
            ->post(route('api-keys.store'), [
                'name' => 'bad-key',
                'permission' => 'admin',
            ])
            ->assertSessionHasErrors('permission');
    }
}
