<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DNSProvider;
use App\Models\PersonalAccessToken;
use App\Models\Project;
use App\Models\ServerProvider;
use App\Models\SourceControl;
use App\Models\StorageProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Tests\CreatesApplication;

class ApiKeyProjectScopeBypassTest extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    public function test_scoped_api_key_cannot_read_providers_outside_its_projects(): void
    {
        [$user, $project1, $project2] = $this->fixtures();

        $scoped = SourceControl::query()->create([
            'user_id' => $user->id,
            'project_id' => $project1->id,
            'provider' => 'github',
            'profile' => 'project-1-source',
            'provider_data' => ['token' => 'project-1-secret'],
        ]);

        $outside = SourceControl::query()->create([
            'user_id' => $user->id,
            'project_id' => $project2->id,
            'provider' => 'github',
            'profile' => 'project-2-source',
            'provider_data' => ['token' => 'project-2-secret'],
        ]);

        $global = SourceControl::query()->create([
            'user_id' => $user->id,
            'project_id' => null,
            'provider' => 'gitea',
            'url' => 'https://gitea.example/',
            'profile' => 'global-source',
            'provider_data' => ['token' => 'global-secret'],
        ]);

        $token = $user->createToken('project-1-only', ['read', 'project:'.$project1->id]);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/source-controls')
            ->assertOk()
            ->assertJsonFragment(['id' => $scoped->id, 'name' => 'project-1-source'])
            ->assertJsonFragment(['id' => $global->id, 'name' => 'global-source'])
            ->assertJsonMissing(['id' => $outside->id, 'name' => 'project-2-source']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/projects')
            ->assertOk()
            ->assertJsonFragment(['id' => $project1->id])
            ->assertJsonMissing(['id' => $project2->id]);
    }

    public function test_write_scoped_api_key_cannot_delete_provider_outside_its_projects(): void
    {
        [$user, $project1, $project2] = $this->fixtures();

        $outside = SourceControl::query()->create([
            'user_id' => $user->id,
            'project_id' => $project2->id,
            'provider' => 'github',
            'profile' => 'project-2-source',
            'provider_data' => ['token' => 'project-2-secret'],
        ]);

        $token = $user->createToken('project-1-only-write', ['read', 'write', 'project:'.$project1->id]);

        $this->withToken($token->plainTextToken)
            ->deleteJson('/api/source-controls/'.$outside->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted('source_controls', ['id' => $outside->id]);
    }

    public function test_scoped_api_key_cannot_read_storage_server_and_dns_providers_outside_its_projects(): void
    {
        [$user, $project1, $project2] = $this->fixtures();

        $allowedStorage = StorageProvider::query()->create([
            'user_id' => $user->id,
            'project_id' => $project1->id,
            'provider' => 's3',
            'profile' => 'project-1-storage',
            'credentials' => ['key' => 'secret-key'],
        ]);

        $storage = StorageProvider::query()->create([
            'user_id' => $user->id,
            'project_id' => $project2->id,
            'provider' => 's3',
            'profile' => 'project-2-storage',
            'credentials' => ['key' => 'secret-key'],
        ]);

        $server = ServerProvider::query()->create([
            'user_id' => $user->id,
            'project_id' => $project2->id,
            'provider' => 'custom',
            'profile' => 'project-2-server',
            'credentials' => ['token' => 'secret-token'],
        ]);

        $dns = DNSProvider::query()->create([
            'user_id' => $user->id,
            'project_id' => $project2->id,
            'provider' => 'cloudflare',
            'name' => 'project-2-dns',
            'credentials' => ['token' => 'secret-token'],
        ]);

        $token = $user->createToken('project-1-only', ['read', 'project:'.$project1->id]);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/storage-providers')
            ->assertOk()
            ->assertJsonFragment(['id' => $allowedStorage->id, 'name' => 'project-1-storage'])
            ->assertJsonMissing(['id' => $storage->id, 'name' => 'project-2-storage']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/server-providers')
            ->assertOk()
            ->assertJsonMissing(['id' => $server->id, 'name' => 'project-2-server']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/dns-providers')
            ->assertOk()
            ->assertJsonMissing(['id' => $dns->id, 'name' => 'project-2-dns']);
    }

    public function test_scoped_api_key_cannot_create_global_provider(): void
    {
        [$user, $project1] = $this->fixtures();

        $token = $user->createToken('project-1-only-write', ['read', 'write', 'project:'.$project1->id]);

        $this->withToken($token->plainTextToken)
            ->postJson('/api/source-controls', [
                'name' => 'global-source',
                'provider' => 'github',
                'token' => 'global-secret',
                'global' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('source_controls', [
            'user_id' => $user->id,
            'profile' => 'global-source',
            'project_id' => null,
        ]);
    }

    public function test_project_endpoint_creates_in_requested_project(): void
    {
        [$user, $project1, $project2] = $this->fixtures();

        $user->update(['current_project_id' => $project2->id]);

        $token = $user->createToken('project-1-only-write', ['read', 'write', 'project:'.$project1->id]);

        Http::fake();

        $this->withToken($token->plainTextToken)
            ->postJson('/api/projects/'.$project1->id.'/source-controls', [
                'name' => 'requested-project-source',
                'provider' => 'github',
                'token' => 'project-1-secret',
            ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'requested-project-source',
                'project_id' => $project1->id,
            ]);

        $this->assertDatabaseHas('source_controls', [
            'user_id' => $user->id,
            'profile' => 'requested-project-source',
            'project_id' => $project1->id,
        ]);
    }

    /**
     * @return array{0: User, 1: Project, 2: Project}
     */
    private function fixtures(): array
    {
        $user = User::factory()->create();
        $project1 = $user->ensureHasDefaultProject();

        $project2 = Project::factory()->create();
        $project2->users()->create([
            'user_id' => $user->id,
            'role' => UserRole::ADMIN,
        ]);

        return [$user, $project1, $project2];
    }
}
