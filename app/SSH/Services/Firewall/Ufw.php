<?php

namespace App\SSH\Services\Firewall;

use App\Enums\FirewallRuleStatus;
use App\Exceptions\SSHError;

class Ufw extends AbstractFirewall
{
    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.firewall.ufw.install-ufw'),
            'install-ufw'
        );
        $this->service->server->os()->cleanup();
    }

    public function uninstall(): void
    {
        //
    }

    /**
     * @throws SSHError
     */
    public function applyRules(): void
    {
        $rules = $this->service->server
            ->firewallRules()
            ->where('status', '!=', FirewallRuleStatus::DELETING)
            ->get();

        $this->service->server->ssh()->exec(
            view('ssh.services.firewall.ufw.apply-rules', compact('rules')),
            'apply-rules'
        );
    }
}
