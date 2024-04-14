<?php

namespace App\SSH\Services\Firewall;

use App\SSH\HasScripts;

class Ufw extends AbstractFirewall
{
    use HasScripts;

    public function install(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('ufw/install-ufw.sh'),
            'install-ufw'
        );
        $this->service->server->os()->cleanup();
    }

    public function uninstall(): void
    {
        //
    }

    public function addRule(string $type, string $protocol, int $port, string $source, ?string $mask): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('ufw/add-rule.sh', [
                'type' => $type,
                'protocol' => $protocol,
                'port' => $port,
                'source' => $source,
                'mask' => $mask || $mask == 0 ? '/'.$mask : '',
            ]),
            'add-firewall-rule'
        );
    }

    public function removeRule(string $type, string $protocol, int $port, string $source, ?string $mask): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('ufw/remove-rule.sh', [
                'type' => $type,
                'protocol' => $protocol,
                'port' => $port,
                'source' => $source,
                'mask' => $mask || $mask == 0 ? '/'.$mask : '',
            ]),
            'remove-firewall-rule'
        );
    }
}
