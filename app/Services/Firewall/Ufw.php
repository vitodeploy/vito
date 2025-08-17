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
        $this->createBasicFirewallRules();

        $this->service->server->ssh()->exec(
            view('ssh.services.firewall.ufw.install-ufw'),
            'install-ufw'
        );
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    public function uninstall(): void
    {
        event('service.uninstalled', $this->service);
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

    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'ufw --version | grep -oE \'[0-9]+\.[0-9]+\.[0-9]+\''
        );

        return trim($version);
    }
}
