<?php

namespace App\SSH\OS;

use App\Models\Server;
use App\Models\ServerLog;
use App\SSH\HasScripts;

class OS
{
    use HasScripts;

    public function __construct(protected Server $server)
    {
    }

    public function installDependencies(): void
    {
        $this->server->ssh()->exec(
            $this->getScript('install-dependencies.sh'),
            'install-dependencies'
        );
    }

    public function upgrade(): void
    {
        $this->server->ssh()->exec(
            $this->getScript('upgrade.sh'),
            'upgrade'
        );
    }

    public function createUser(string $user, string $password, string $key): void
    {
        $this->server->ssh()->exec(
            $this->getScript('create-user.sh', [
                'user' => $user,
                'password' => $password,
                'key' => $key,
            ]),
            'create-user'
        );
    }

    public function getPublicKey(string $user): string
    {
        return $this->server->ssh()->exec(
            $this->getScript('read-file.sh', [
                'path' => '/home/'.$user.'/.ssh/id_rsa.pub',
            ])
        );
    }

    public function deploySSHKey(string $key): void
    {
        $this->server->ssh()->exec(
            $this->getScript('deploy-ssh-key.sh', [
                'key' => $key,
            ]),
            'deploy-ssh-key'
        );
    }

    public function deleteSSHKey(string $key): void
    {
        $this->server->ssh()->exec(
            $this->getScript('delete-ssh-key.sh', [
                'key' => $key,
                'user' => $this->server->getSshUser(),
            ]),
            'delete-ssh-key'
        );
    }

    public function generateSSHKey(string $name): void
    {
        $this->server->ssh()->exec(
            $this->getScript('generate-ssh-key.sh', [
                'name' => $name,
            ]),
            'generate-ssh-key'
        );
    }

    public function readSSHKey(string $name): string
    {
        return $this->server->ssh()->exec(
            $this->getScript('read-ssh-key.sh', [
                'name' => $name,
            ]),
        );
    }

    public function reboot(): void
    {
        $this->server->ssh()->exec(
            $this->getScript('reboot.sh'),
        );
    }

    public function editFile(string $path, string $content): void
    {
        $this->server->ssh()->exec(
            $this->getScript('edit-file.sh', [
                'path' => $path,
                'content' => $content,
            ]),
        );
    }

    public function readFile(string $path): string
    {
        return $this->server->ssh()->exec(
            $this->getScript('read-file.sh', [
                'path' => $path,
            ])
        );
    }

    public function runScript(string $path, string $script, ?int $siteId = null): ServerLog
    {
        $ssh = $this->server->ssh();
        $ssh->exec(
            $this->getScript('run-script.sh', [
                'path' => $path,
                'script' => $script,
            ]),
            'run-script',
            $siteId
        );

        return $ssh->log;
    }

    public function download(string $url, string $path): string
    {
        return $this->server->ssh()->exec(
            $this->getScript('download.sh', [
                'url' => $url,
                'path' => $path,
            ])
        );
    }

    public function unzip(string $path): string
    {
        return $this->server->ssh()->exec(
            'unzip '.$path
        );
    }
}
