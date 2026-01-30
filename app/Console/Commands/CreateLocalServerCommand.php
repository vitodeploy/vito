<?php

namespace App\Console\Commands;

use App\Enums\FirewallRuleStatus;
use App\Enums\OperatingSystem;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Models\FirewallRule;
use App\Models\Project;
use App\Models\Server;
use App\Models\Service;
use App\Models\User;
use App\ServerProviders\Custom;
use Illuminate\Console\Command;

class CreateLocalServerCommand extends Command
{
    protected $signature = 'servers:create-local
        {ip : The local server IP address}
        {--ports= : Comma-separated list of open ports}
        {--nginx= : Whether Nginx is installed (Y/N)}
        {--name=local-server : The server name}
        {--user= : The user ID to assign the server to}
        {--project= : The project ID to assign the server to}
        {--domain= : The domain for the local server}
        {--web-port=3000 : The web port for the local server}
        {--ssl= : Whether SSL is enabled (Y/N)}';

    protected $description = 'Create a local server with pre-installed services';

    public function handle(): int
    {
        $ip = $this->argument('ip');
        $ports = $this->option('ports') ? explode(',', $this->option('ports')) : [];
        $nginxInstalled = strtoupper($this->option('nginx') ?? 'N') === 'Y';
        $name = $this->option('name');
        $domain = $this->option('domain');
        $webPort = (int) $this->option('web-port');
        $sslEnabled = strtoupper($this->option('ssl') ?? 'N') === 'Y';

        $user = $this->getUser();
        if (! $user) {
            $this->error('No user found. Please create a user first or specify a valid user ID.');

            return Command::FAILURE;
        }

        $project = $this->getProject($user);
        if (! $project) {
            $this->error('No project found. Please create a project first or specify a valid project ID.');

            return Command::FAILURE;
        }

        $existingServer = Server::query()->where('ip', $ip)->first();
        if ($existingServer) {
            $this->error("A server with IP {$ip} already exists.");

            return Command::FAILURE;
        }

        $this->info("Creating local server '{$name}' with IP {$ip}...");

        $server = Server::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'name' => $name,
            'ssh_user' => config('core.ssh_user'),
            'ip' => $ip,
            'local_ip' => $ip,
            'port' => 22,
            'os' => OperatingSystem::UBUNTU22,
            'provider' => Custom::id(),
            'authentication' => [
                'user' => config('core.ssh_user'),
                'pass' => '',
            ],
            'public_key' => '',
            'status' => ServerStatus::READY,
            'progress' => 100,
            'is_local' => true,
            'local_data' => [
                'domain' => $domain,
                'port' => $webPort,
                'ssl_enabled' => $sslEnabled,
            ],
        ]);

        $this->info("Server created with ID: {$server->id}");

        if ($nginxInstalled) {
            $this->createNginxService($server);
            $this->info('Nginx service created.');
        }

        if (! empty($ports)) {
            $this->createFirewallRules($server, $ports);
            $this->info('Firewall rules created for ports: '.implode(', ', $ports));
        }

        $this->info('Local server created successfully!');

        return Command::SUCCESS;
    }

    private function getUser(): ?User
    {
        $userId = $this->option('user');

        if ($userId) {
            return User::query()->find($userId);
        }

        return User::query()->first();
    }

    private function getProject(User $user): ?Project
    {
        $projectId = $this->option('project');

        if ($projectId) {
            return Project::query()->find($projectId);
        }

        /** @var Project|null $project */
        $project = $user->currentProject ?? $user->projects()->first();

        return $project;
    }

    private function createNginxService(Server $server): void
    {
        Service::query()->create([
            'server_id' => $server->id,
            'type' => 'webserver',
            'name' => 'nginx',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
            'is_default' => true,
        ]);
    }

    private function createFirewallRules(Server $server, array $ports): void
    {
        foreach ($ports as $port) {
            $port = trim($port);
            if (! is_numeric($port)) {
                $this->warn("Skipping invalid port: {$port}");

                continue;
            }

            FirewallRule::query()->create([
                'server_id' => $server->id,
                'name' => "port-{$port}",
                'type' => 'allow',
                'protocol' => 'tcp',
                'port' => (int) $port,
                'source' => '0.0.0.0',
                'mask' => '0',
                'note' => 'Created by servers:create-local command',
                'status' => FirewallRuleStatus::READY,
            ]);
        }
    }
}
