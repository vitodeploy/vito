<?php

namespace App\SSH\Services\Firewall;

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
    public function addRule(string $type, string $protocol, int $port, string $source, ?string $mask): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.firewall.ufw.add-rule', [
                'type' => $type,
                'protocol' => $protocol,
                'port' => $port,
                'source' => $source,
                'mask' => $mask || $mask === 0 ? '/'.$mask : '',
            ]),
            'add-firewall-rule'
        );
    }

    /**
     * @throws SSHError
     */
    public function removeRule(string $type, string $protocol, int $port, string $source, ?string $mask): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.firewall.ufw.remove-rule', [
                'type' => $type,
                'protocol' => $protocol,
                'port' => $port,
                'source' => $source,
                'mask' => $mask || $mask === 0 ? '/'.$mask : '',
            ]),
            'remove-firewall-rule'
        );
    }
}
