<?php

namespace App\Jobs\Installation;

use App\Actions\FirewallRule\CreateRule;
use App\Enums\ServiceStatus;
use App\Jobs\Job;
use App\Models\FirewallRule;
use App\Models\Service;
use App\SSHCommands\PHPMyAdmin\CreateNginxPHPMyAdminVHostCommand;
use App\SSHCommands\PHPMyAdmin\DownloadPHPMyAdminCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

/**
 * @deprecated
 */
class InstallPHPMyAdmin extends Job
{
    protected Service $service;

    protected ?FirewallRule $firewallRule;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->setUpFirewall();
        $this->downloadSource();
        $this->setUpVHost();
        $this->restartPHP();
        $this->service->update([
            'status' => ServiceStatus::READY,
        ]);
    }

    /**
     * @throws Throwable
     */
    private function setUpFirewall(): void
    {
        $this->firewallRule = FirewallRule::query()
            ->where('server_id', $this->service->server_id)
            ->where('port', $this->service->type_data['port'])
            ->first();
        if ($this->firewallRule) {
            $this->firewallRule->source = $this->service->type_data['allowed_ip'];
            $this->firewallRule->save();
        } else {
            $this->firewallRule = app(CreateRule::class)->create(
                $this->service->server,
                [
                    'type' => 'allow',
                    'protocol' => 'tcp',
                    'port' => $this->service->type_data['port'],
                    'source' => $this->service->type_data['allowed_ip'],
                    'mask' => '0',
                ]
            );
        }
    }

    /**
     * @throws Throwable
     */
    private function downloadSource(): void
    {
        $this->service->server->ssh()->exec(
            new DownloadPHPMyAdminCommand(),
            'download-phpmyadmin'
        );
    }

    /**
     * @throws Throwable
     */
    private function setUpVHost(): void
    {
        $vhost = File::get(resource_path('commands/webserver/nginx/phpmyadmin-vhost.conf'));
        $vhost = Str::replace('__php_version__', $this->service->server->defaultService('php')->version, $vhost);
        $vhost = Str::replace('__port__', $this->service->type_data['port'], $vhost);
        $this->service->server->ssh()->exec(
            new CreateNginxPHPMyAdminVHostCommand($vhost),
            'create-phpmyadmin-vhost'
        );
    }

    private function restartPHP(): void
    {
        $this->service->server->service(
            'php',
            $this->service->type_data['php']
        )?->restart();
    }

    /**
     * @throws Throwable
     */
    public function failed(Throwable $throwable): Throwable
    {
        $this->firewallRule?->removeFromServer();
        $this->service->update([
            'status' => ServiceStatus::INSTALLATION_FAILED,
        ]);
        throw $throwable;
    }
}
