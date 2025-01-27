<?php

namespace App\SSH\OS;

use App\Exceptions\SSHError;
use App\Exceptions\SSHUploadFailed;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Site;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

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
        $site->server->ssh($site->user)->exec(
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
        return $site->server->ssh($site->user)->exec(
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
     * @throws SSHUploadFailed
     */
    public function editFile(string $path, ?string $content = null): void
    {
        $tmpName = Str::random(10).strtotime('now');
        try {
            /** @var FilesystemAdapter $storageDisk */
            $storageDisk = Storage::disk('local');
            $storageDisk->put($tmpName, $content);
            $this->server->ssh()->upload(
                $storageDisk->path($tmpName),
                $path
            );
        } catch (Throwable) {
            throw new SSHUploadFailed;
        } finally {
            $this->deleteTempFile($tmpName);
        }
    }

    /**
     * @throws SSHError
     */
    public function readFile(string $path): string
    {
        return $this->server->ssh()->exec(
            view('ssh.os.read-file', [
                'path' => $path,
            ])
        );
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
     * @throws SSHError
     */
    public function runScript(string $path, string $script, ?ServerLog $serverLog, ?string $user = null, ?array $variables = []): ServerLog
    {
        $ssh = $this->server->ssh($user);
        if ($serverLog) {
            $ssh->setLog($serverLog);
        }
        $command = '';
        foreach ($variables as $key => $variable) {
            $command .= "$key=$variable\n";
        }
        $command .= view('ssh.os.run-script', [
            'path' => $path,
            'script' => $script,
        ]);
        $ssh->exec($command, 'run-script');

        info($command);

        return $ssh->log;
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
    public function unzip(string $path): string
    {
        return $this->server->ssh()->exec(
            'unzip '.$path
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
    public function deleteFile(string $path): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.delete-file', [
                'path' => $path,
            ]),
            'delete-file'
        );
    }

    private function deleteTempFile(string $name): void
    {
        if (Storage::disk('local')->exists($name)) {
            Storage::disk('local')->delete($name);
        }
    }
}
