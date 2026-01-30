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
use App\Services\Firewall\Ufw;
use App\Services\Local\VitoLocal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

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

        // Validate IP address
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error("Invalid IP address: {$ip}");

            return Command::FAILURE;
        }

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

        try {
            /** @var Server $server */
            $server = DB::transaction(function () use ($project, $user, $name, $ip, $domain, $webPort, $sslEnabled, $nginxInstalled, $ports) {
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

                // Create default services for local server
                $this->createVitoLocalService($server);
                $this->createFirewallService($server);

                if ($nginxInstalled) {
                    $this->createNginxService($server);
                }

                if (! empty($ports)) {
                    $this->createFirewallRules($server, $ports);
                }

                return $server;
            });
        } catch (Throwable $e) {
            $this->error('Failed to create local server: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info("Server created with ID: {$server->id}");
        $this->info('VitoLocal service created.');
        $this->info('Firewall service created.');

        if ($nginxInstalled) {
            $this->info('Nginx service created.');
        }

        if (! empty($ports)) {
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

    private function createVitoLocalService(Server $server): void
    {
        Service::query()->create([
            'server_id' => $server->id,
            'type' => VitoLocal::type(),
            'name' => VitoLocal::id(),
            'version' => 'latest',
            'status' => ServiceStatus::READY,
            'is_default' => true,
        ]);
    }

    private function createFirewallService(Server $server): void
    {
        Service::query()->create([
            'server_id' => $server->id,
            'type' => Ufw::type(),
            'name' => Ufw::id(),
            'version' => 'latest',
            'status' => ServiceStatus::READY,
            'is_default' => true,
        ]);
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
