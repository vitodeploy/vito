<?php

namespace App\SSH\OS;

use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Site;

class OS
{
    public function __construct(protected Server $server) {}

    /**
     * @throws SSHError
     */
    public function installDependencies(): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.install-dependencies', [
                'name' => $this->server->creator->name,
                'email' => $this->server->creator->email,
            ]),
            'install-dependencies'
        );
    }

    /**
     * @throws SSHError
     */
    public function upgrade(): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.upgrade'),
            'upgrade'
        );
    }

    /**
     * @throws SSHError
     */
    public function availableUpdates(): int
    {
        $result = $this->server->ssh()->exec(
            view('ssh.os.available-updates'),
            'check-available-updates'
        );

        // -1 because the first line is not a package
        $availableUpdates = str($result)->after('Available updates:')->trim()->toInteger() - 1;

        return max($availableUpdates, 0);
    }

    /**
     * @throws SSHError
     */
    public function createUser(string $user, string $password, string $key): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.create-user', [
                'user' => $user,
                'password' => $password,
                'key' => $key,
            ]),
            'create-user'
        );
    }

    /**
     * @throws SSHError
     */
    public function createIsolatedUser(string $user, string $password, int $site_id): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.create-isolated-user', [
                'user' => $user,
                'serverUser' => $this->server->getSshUser(),
                'password' => $password,
            ]),
            'create-isolated-user',
            $site_id
        );
    }

    /**
     * @throws SSHError
     */
    public function deleteIsolatedUser(string $user): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.delete-isolated-user', [
                'user' => $user,
                'serverUser' => $this->server->getSshUser(),
            ]),
            'delete-isolated-user'
        );
    }

    /**
     * @throws SSHError
     */
    public function getPublicKey(string $user): string
    {
        return $this->server->ssh()->exec(
            view('ssh.os.read-file', [
                'path' => '/home/'.$user.'/.ssh/id_rsa.pub',
            ])
        );
    }

    /**
     * @throws SSHError
     */
    public function deploySSHKey(string $key): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.deploy-ssh-key', [
                'key' => $key,
            ]),
            'deploy-ssh-key'
        );
    }

    /**
     * @throws SSHError
     */
    public function deleteSSHKey(string $key): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.delete-ssh-key', [
                'key' => $key,
                'user' => $this->server->getSshUser(),
            ]),
            'delete-ssh-key'
        );
    }

    /**
     * @throws SSHError
     */
    public function generateSSHKey(string $name, ?Site $site = null): void
    {
        $this->server->ssh($site?->user)->exec(
            view('ssh.os.generate-ssh-key', [
                'name' => $name,
            ]),
            'generate-ssh-key',
            $site?->id
        );
    }

    /**
     * @throws SSHError
     */
    public function readSSHKey(string $name, ?Site $site = null): string
    {
        return $this->server->ssh($site?->user)->exec(
            view('ssh.os.read-ssh-key', [
                'name' => $name,
            ]),
        );
    }

    /**
     * @throws SSHError
     */
    public function reboot(): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.reboot'),
        );
    }

    /**
     * @deprecated use write() instead
     *
     * @throws SSHError
     */
    public function editFileAs(string $path, string $user, ?string $content = null): void
    {
        $sudo = $user === 'root';
        $actualUser = $sudo ? $this->server->getSshUser() : $user;

        $this->server->ssh($actualUser)->exec(
            view('ssh.os.edit-file', [
                'path' => $path,
                'content' => $content,
                'sudo' => $sudo,
            ]),
            'edit-file'
        );
    }

    /**
     * @throws SSHError
     */
    public function readFile(string $path): string
    {
        return trim($this->server->ssh()->exec(
            view('ssh.os.read-file', [
                'path' => $path,
            ])
        ));
    }

    /**
     * @throws SSHError
     */
    public function tail(string $path, int $lines): string
    {
        return $this->server->ssh()->exec(
            view('ssh.os.tail', [
                'path' => $path,
                'lines' => $lines,
            ])
        );
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws SSHError
     */
    public function runScript(string $path, string $script, ?ServerLog $serverLog, ?string $user = null, ?array $variables = []): ServerLog
    {
        $ssh = $this->server->ssh($user);
        if ($serverLog instanceof ServerLog) {
            $ssh->setLog($serverLog);
        }
        $command = '';
        if ($variables !== null && $variables !== []) {
            foreach ($variables as $key => $variable) {
                $command .= "$key=$variable\n";
            }
        }
        $command .= view('ssh.os.run-script', [
            'path' => $path,
            'script' => $script,
        ]);
        $ssh->exec($command, 'run-script');

        /** @var ServerLog $log */
        $log = $ssh->log;

        return $log;
    }

    /**
     * @throws SSHError
     */
    public function download(string $url, string $path): string
    {
        return $this->server->ssh()->exec(
            view('ssh.os.download', [
                'url' => $url,
                'path' => $path,
            ])
        );
    }

    /**
     * @throws SSHError
     */
    public function extract(string $path, ?string $destination = null, ?string $user = null): void
    {
        $this->server->ssh($user)->exec(
            view('ssh.os.extract', [
                'path' => $path,
                'destination' => $destination,
            ]),
            'extract'
        );
    }

    /**
     * @throws SSHError
     */
    public function cleanup(): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.cleanup'),
            'cleanup'
        );
    }

    /**
     * @return array<string, string>
     *
     * @throws SSHError
     */
    public function resourceInfo(): array
    {
        $info = $this->server->ssh()->exec(
            view('ssh.os.resource-info'),
        );

        return [
            'load' => str($info)->after('load:')->before(PHP_EOL)->toString(),
            'memory_total' => str($info)->after('memory_total:')->before(PHP_EOL)->toString(),
            'memory_used' => str($info)->after('memory_used:')->before(PHP_EOL)->toString(),
            'memory_free' => str($info)->after('memory_free:')->before(PHP_EOL)->toString(),
            'disk_total' => str($info)->after('disk_total:')->before(PHP_EOL)->toString(),
            'disk_used' => str($info)->after('disk_used:')->before(PHP_EOL)->toString(),
            'disk_free' => str($info)->after('disk_free:')->before(PHP_EOL)->toString(),
        ];
    }

    /**
     * @throws SSHError
     */
    public function deleteFile(string $path, ?string $user = null): void
    {
        $this->server->ssh($user)->exec(
            view('ssh.os.delete-file', [
                'path' => $path,
            ]),
            'delete-file'
        );
    }

    /**
     * @throws SSHError
     */
    public function ls(string $path, ?string $user = null): string
    {
        return $this->server->ssh($user)->exec('ls -la '.$path);
    }

    /**
     * @throws SSHError
     */
    public function write(string $path, string $content, ?string $user = null): void
    {
        $this->server->ssh()->write(
            $path,
            $content,
            $user
        );
    }

    /**
     * @throws SSHError
     */
    public function mkdir(string $path, ?string $user = null): string
    {
        return $this->server->ssh($user)->exec('mkdir -p '.$path);
    }
}
