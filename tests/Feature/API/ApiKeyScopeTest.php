<?php

namespace Tests\Feature\API;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiKeyScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_access_token_can_see_all_projects(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $this->json('GET', '/api/projects')
            ->assertSuccessful()
            ->assertJsonFragment(['id' => $this->user->currentProject->id])
            ->assertJsonFragment(['id' => $project2->id]);
    }

    public function test_token_with_no_project_scope_has_access_to_all(): void
    {
        $token = $this->user->createToken('full-access-token', ['read', 'write']);

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $token->accessToken;

        $this->assertTrue($accessToken->hasProjectAccess($this->user->currentProject));

        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $this->assertTrue($accessToken->hasProjectAccess($project2));
        $this->assertEmpty($accessToken->getProjectIds());
    }

    public function test_scoped_token_has_project_ids_in_abilities(): void
    {
        $projectId = $this->user->current_project_id;
        $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$projectId]);

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $token->accessToken;

        $this->assertEquals([$projectId], $accessToken->getProjectIds());
        $this->assertTrue($accessToken->hasProjectAccess($this->user->currentProject));
    }

    public function test_scoped_token_denies_access_to_unscoped_project(): void
    {
        /** @var Project $project1 */
        $project1 = Project::factory()->create();
        $project1->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$project1->id]);

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $token->accessToken;

        $this->assertTrue($accessToken->hasProjectAccess($project1));
        $this->assertFalse($accessToken->hasProjectAccess($project2));
    }

    public function test_scoped_token_cannot_access_servers_in_unscoped_project(): void
    {
        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        // Token scoped to project2 only
        $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$project2->id]);

        // Try accessing servers in the default project (not scoped)
        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('GET', route('api.projects.servers', [
                'project' => $this->user->current_project_id,
            ]))
            ->assertForbidden();
    }

    public function test_scoped_token_can_access_servers_in_scoped_project(): void
    {
        $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$this->user->current_project_id]);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('GET', route('api.projects.servers', [
                'project' => $this->user->current_project_id,
            ]))
            ->assertSuccessful();
    }

    public function test_full_access_token_can_access_any_project_servers(): void
    {
        $token = $this->user->createToken('full-access-token', ['read', 'write']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('GET', route('api.projects.servers', [
                'project' => $this->user->current_project_id,
            ]))
            ->assertSuccessful();
    }

    public function test_read_only_token_cannot_write(): void
    {
        $token = $this->user->createToken('read-only-token', ['read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('POST', '/api/projects', [
                'name' => 'test-project',
            ])
            ->assertForbidden();
    }

    public function test_read_only_token_can_read(): void
    {
        $token = $this->user->createToken('read-only-token', ['read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('GET', '/api/projects')
            ->assertSuccessful();
    }

    public function test_scoped_token_filters_project_index(): void
    {
        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $token = $this->user->createToken('scoped-token', ['read', 'project:'.$project2->id]);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('GET', '/api/projects')
            ->assertSuccessful()
            ->assertJsonFragment(['id' => $project2->id])
            ->assertJsonMissing(['id' => $this->user->currentProject->id]);
    }

    public function test_scoped_token_cannot_show_unscoped_project(): void
    {
        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $token = $this->user->createToken('scoped-token', ['read', 'project:'.$this->user->current_project_id]);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('GET', "/api/projects/{$project2->id}")
            ->assertForbidden();
    }

    public function test_scoped_token_can_show_scoped_project(): void
    {
        $token = $this->user->createToken('scoped-token', ['read', 'project:'.$this->user->current_project_id]);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('GET', "/api/projects/{$this->user->currentProject->id}")
            ->assertSuccessful();
    }

    public function test_scoped_token_cannot_update_unscoped_project(): void
    {
        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$this->user->current_project_id]);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('PUT', "/api/projects/{$project2->id}", [
                'name' => 'updated-name',
            ])
            ->assertForbidden();
    }

    public function test_scoped_token_can_update_scoped_project(): void
    {
        $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$this->user->current_project_id]);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('PUT', "/api/projects/{$this->user->currentProject->id}", [
                'name' => 'updated-name',
            ])
            ->assertSuccessful();
    }

    public function test_scoped_token_cannot_delete_unscoped_project(): void
    {
        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$this->user->current_project_id]);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('DELETE', "/api/projects/{$project2->id}", [
                'name' => $project2->name,
            ])
            ->assertForbidden();
    }

    public function test_scoped_token_can_delete_scoped_project(): void
    {
        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::OWNER,
        ]);

        $token = $this->user->createToken('scoped-token', ['read', 'write', 'project:'.$project2->id]);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('DELETE', "/api/projects/{$project2->id}", [
                'name' => $project2->name,
            ])
            ->assertSuccessful();
    }

    public function test_multiple_project_scopes(): void
    {
        /** @var Project $project2 */
        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $token = $this->user->createToken('multi-scoped', [
            'read',
            'write',
            'project:'.$this->user->current_project_id,
            'project:'.$project2->id,
        ]);

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $token->accessToken;

        $this->assertTrue($accessToken->hasProjectAccess($this->user->currentProject));
        $this->assertTrue($accessToken->hasProjectAccess($project2));
        $this->assertCount(2, $accessToken->getProjectIds());
    }
}
