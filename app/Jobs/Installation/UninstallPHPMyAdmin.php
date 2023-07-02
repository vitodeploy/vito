<?php

namespace App\Jobs\Installation;

use App\Jobs\Job;
use App\Models\FirewallRule;
use App\Models\Service;
use App\SSHCommands\DeleteNginxPHPMyAdminVHost;
use Exception;
use Throwable;

class UninstallPHPMyAdmin extends Job
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->removeFirewallRule();
        $this->deleteVHost();
        $this->restartPHP();
    }

    /**
     * @throws Exception
     */
    private function removeFirewallRule(): void
    {
        /** @var ?FirewallRule $rule */
        $rule = FirewallRule::query()
            ->where('server_id', $this->service->server_id)
            ->where('port', '54331')
            ->first();
        $rule?->removeFromServer();
    }

    /**
     * @throws Throwable
     */
    private function deleteVHost(): void
    {
        $this->service->server->ssh()->exec(
            new DeleteNginxPHPMyAdminVHost('/home/vito/phpmyadmin'),
            'delete-phpmyadmin-vhost'
        );
    }

    private function restartPHP(): void
    {
        $this->service->server->service(
            'php',
            $this->service->type_data['php']
        )?->restart();
    }
}
