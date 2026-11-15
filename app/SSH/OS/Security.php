<?php

namespace App\SSH\OS;

use App\Enums\OperatingSystem;
use App\Exceptions\SSHError;
use App\Models\Server;

class Security
{
    public function __construct(protected Server $server) {}

    /**
     * @throws SSHError
     */
    public function passwordAuthEnabled(): bool
    {
        $result = $this->server->ssh()->exec(
            view('ssh.security.password-auth-status'),
            'password-auth-status'
        );

        return ! str($result)->after('VITO_PASSWORD_AUTH:')->trim()->startsWith('no');
    }

    /**
     * @throws SSHError
     */
    public function setPasswordAuth(bool $enabled): void
    {
        $view = $enabled ? 'ssh.security.enable-password-auth' : 'ssh.security.disable-password-auth';

        $this->server->ssh()->exec(
            view($view, [
                'useDropin' => $this->supportsConfigDropin(),
            ]),
            $enabled ? 'enable-password-auth' : 'disable-password-auth'
        );
    }

    /**
     * @throws SSHError
     */
    public function rootLoginEnabled(): bool
    {
        $result = $this->server->ssh()->exec(
            view('ssh.security.root-login-status'),
            'root-login-status'
        );

        return ! str($result)->after('VITO_ROOT_LOGIN:')->trim()->startsWith('no');
    }

    /**
     * @throws SSHError
     */
    public function setRootLogin(bool $enabled): void
    {
        $view = $enabled ? 'ssh.security.enable-root-login' : 'ssh.security.disable-root-login';

        $this->server->ssh()->exec(
            view($view, [
                'useDropin' => $this->supportsConfigDropin(),
            ]),
            $enabled ? 'enable-root-login' : 'disable-root-login'
        );
    }

    private function supportsConfigDropin(): bool
    {
        return $this->server->os !== OperatingSystem::UBUNTU18;
    }
}
