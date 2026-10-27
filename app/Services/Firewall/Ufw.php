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
            ->orderBy('id')
            ->get();

        $managed = $serverRules->where('component_managed', true);
        $denies = $managed->unique('port')->map(function ($rule) {
            $deny = clone $rule;
            $deny->type = 'deny';
            $deny->source = null;
            $deny->mask = null;

            return $deny;
        });
        $managedPorts = $managed->pluck('port')->map(fn ($port): string => (string) $port);
        $ordinary = $networkRules->concat($serverRules->where('component_managed', false)->where('status', '!=', FirewallRuleStatus::DELETING))
            ->reject(fn ($rule): bool => $rule->protocol === 'tcp' && $managedPorts->contains((string) $rule->port)
                && ($rule->source === null || (string) $rule->mask === '0'));
        $resourcePorts = $serverRules->whereNotNull('environment_attachment_id')->pluck('port')->map(fn ($port): string => (string) $port);
        $explicitAllow = fn ($rule): bool => $rule->type === 'allow' && $rule->protocol === 'tcp' && $resourcePorts->contains((string) $rule->port);
        $rules = $managed->where('type', 'allow')->where('status', '!=', FirewallRuleStatus::DELETING)
            ->concat($ordinary->filter($explicitAllow))->concat($denies)->concat($ordinary->reject($explicitAllow));

        $finalize = app(FinalizeServerNetworkRules::class);

        try {
            $server->ssh()->exec(
                view('ssh.services.firewall.ufw.apply-rules', [
                    'rules' => $rules,
                    'requireIpv6' => $resourcePorts->isNotEmpty(),
                    'resourceRules' => $rules->filter(fn ($rule): bool => $rule->component_managed || $explicitAllow($rule))->whereIn('port', $resourcePorts),
                ]),
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
