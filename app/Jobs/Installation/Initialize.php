<?php

namespace App\Jobs\Installation;

use App\Models\Server;
use App\SSHCommands\System\CreateUserCommand;
use App\SSHCommands\System\GetPublicKeyCommand;
use Throwable;

class Initialize extends InstallationJob
{
    protected Server $server;

    protected ?string $asUser;

    public function __construct(Server $server, ?string $asUser = null)
    {
        $this->server = $server->refresh();
        $this->asUser = $asUser;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->authentication();
        $this->publicKey();
        // $this->setHostname();
    }

    /**
     * @throws Throwable
     */
    protected function authentication(): void
    {
        $this->server
            ->ssh($this->asUser ?? $this->server->ssh_user)
            ->exec(
                new CreateUserCommand(
                    $this->server->authentication['user'],
                    $this->server->authentication['pass'],
                    $this->server->sshKey()['public_key']
                ),
                'create-user'
            );

        $this->server->ssh_user = config('core.ssh_user');
        $this->server->save();
    }

    /**
     * @throws Throwable
     */
    protected function publicKey(): void
    {
        $publicKey = $this->server->ssh()->exec(new GetPublicKeyCommand());
        $this->server->update([
            'public_key' => $publicKey,
        ]);
    }

    /**
     * @throws Throwable
     */
    protected function setHostname(): void
    {
        $this->server
            ->ssh()
            ->exec('sudo hostnamectl set-hostname '.$this->server->hostname);
    }
}
