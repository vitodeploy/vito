<?php

namespace App\Services\Firewall;

use App\Actions\Network\FinalizeServerNetworkRules;
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
                view('ssh.services.firewall.ufw.install-ufw', [
                    'sshPort' => $this->service->server->port ?? 22,
                ]),
                'install-ufw'
            );

        $this->applyRules();

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
        $server = $this->service->server;

        $networkRules = $server->networkRules()
            ->where('status', '!=', FirewallRuleStatus::DELETING)
            ->ordered()
            ->get();

        $emittedIds = $networkRules->pluck('id')->all();
        $deletingIds = $server->networkRules()->where('status', FirewallRuleStatus::DELETING)->pluck('id')->all();

        $serverRules = $server->firewallRules()
            ->where('status', '!=', FirewallRuleStatus::DELETING)
            ->orderBy('id')
            ->get();

        $rules = $networkRules->concat($serverRules);

        $finalize = app(FinalizeServerNetworkRules::class);

        try {
            $server->ssh()->exec(
                view('ssh.services.firewall.ufw.apply-rules', ['rules' => $rules]),
                'apply-rules'
            );
        } catch (SSHError $e) {
            $finalize->failure($server, $emittedIds);

            throw $e;
        }

        $finalize->success($server, $emittedIds, $deletingIds);
    }

    public function versionCommand(): ?string
    {
        return 'ufw --version | grep -oE \'[0-9]+\.[0-9]+\.[0-9]+\'';
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
