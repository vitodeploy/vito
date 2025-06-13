<?php

namespace App\Services\Firewall;

use App\Enums\FirewallRuleStatus;
use App\Exceptions\SSHError;

class Ufw extends AbstractFirewall
{
    public static function id(): string
    {
        return 'ufw';
    }

    public static function type(): string
    {
        return 'firewall';
    }

    public function unit(): string
    {
        return 'ufw';
    }

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
            view('ssh.services.firewall.ufw.apply-rules', ['rules' => $rules]),
            'apply-rules'
        );
    }
}
