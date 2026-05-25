<?php

namespace App\Services\Firewall;

use App\DTOs\ServiceLog;
use App\Enums\FirewallRuleStatus;
use App\Exceptions\SSHError;
use App\Services\HasLogs;

class Ufw extends AbstractFirewall implements HasLogs
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

        $this->service->server->ssh()
            ->setLog($this->service->log)
            ->exec(
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

    public function logs(): array
    {
        return [
            new ServiceLog(
                key: 'ufw:general',
                serviceLabel: 'UFW',
                label: 'General log',
                source: ServiceLog::SOURCE_FILE,
                target: '/var/log/ufw.log',
            ),
        ];
    }
}
