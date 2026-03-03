<?php

namespace Tests\Feature\WorkflowActions;

use App\Enums\UserRole;
use App\Facades\SSH;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Models\Workflow;
use App\WorkflowActions\Site\CreatePHPBlankSite;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSiteActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_site_action_fails_with_foreign_server(): void
    {
        SSH::fake();

        // Create a second project with a server that the user has no access to
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create();
        $otherProject->users()->create([
            'user_id' => $otherUser->id,
            'role' => UserRole::OWNER,
        ]);
        $otherServer = Server::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $otherProject->id,
        ]);

        // Create a workflow in the user's own project
        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $action = new CreatePHPBlankSite($this->user, $workflow);

        $this->expectException(AuthorizationException::class);

        $action->run([
            'server_id' => $otherServer->id,
            'type' => 'php-blank',
            'domain' => 'cross-project.example.com',
            'aliases' => [],
            'php_version' => '8.2',
            'web_directory' => 'public',
        ]);
    }

    public function test_create_site_action_succeeds_with_own_server(): void
    {
        SSH::fake();

        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $action = new CreatePHPBlankSite($this->user, $workflow);

        $result = $action->run([
            'server_id' => $this->server->id,
            'type' => 'php-blank',
            'domain' => 'my-site.example.com',
            'aliases' => [],
            'user' => 'mysiteuser',
            'php_version' => '8.2',
            'web_directory' => 'public',
        ]);

        $this->assertArrayHasKey('site_id', $result);
        $this->assertEquals('my-site.example.com', $result['site_domain']);
    }
}
