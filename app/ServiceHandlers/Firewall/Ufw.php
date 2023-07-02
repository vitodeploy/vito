<?php

namespace App\ServiceHandlers\Firewall;

use App\SSHCommands\Firewall\AddRuleCommand;
use App\SSHCommands\Firewall\RemoveRuleCommand;
use Throwable;

class Ufw extends AbstractFirewall
{
    /**
     * @throws Throwable
     */
    public function addRule(string $type, string $protocol, int $port, string $source, string $mask): void
    {
        $this->service->server->ssh()->exec(
            new AddRuleCommand('ufw', $type, $protocol, $port, $source, $mask),
            'add-firewall-rule'
        );
    }

    /**
     * @throws Throwable
     */
    public function removeRule(string $type, string $protocol, int $port, string $source, string $mask): void
    {
        $this->service->server->ssh()->exec(
            new RemoveRuleCommand('ufw', $type, $protocol, $port, $source, $mask),
            'remove-firewall-rule'
        );
    }
}
