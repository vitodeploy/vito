<?php

namespace App\SSH\OS;

use App\Exceptions\SSHUploadFailed;
use App\Models\Server;
use App\Models\ServerLog;
use App\SSH\HasScripts;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class OS
{
    use HasScripts;

    public function __construct(protected Server $server) {}

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

    public function availableUpdates(): int
    {
        $result = $this->server->ssh()->exec(
            $this->getScript('available-updates.sh'),
            'check-available-updates'
        );

        // -1 because the first line is not a package
        $availableUpdates = str($result)->after('Available updates:')->trim()->toInteger() - 1;

        return max($availableUpdates, 0);
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
            throw new SSHUploadFailed();
        } finally {
            $this->deleteTempFile($tmpName);
        }
    }

    public function readFile(string $path): string
    {
        return $this->server->ssh()->exec(
            $this->getScript('read-file.sh', [
                'path' => $path,
            ])
        );
    }

    public function tail(string $path, int $lines): string
    {
        return $this->server->ssh()->exec(
            $this->getScript('tail.sh', [
                'path' => $path,
                'lines' => $lines,
            ])
        );
    }

    public function runScript(string $path, string $script, ?ServerLog $serverLog, ?string $user = null, ?array $variables = []): ServerLog
    {
        $ssh = $this->server->ssh($user);
        if ($serverLog) {
            $ssh->setLog($serverLog);
        }
        $command = '';
        foreach ($variables as $key => $variable) {
            $command .= "$key=$variable".PHP_EOL;
        }
        $command .= $this->getScript('run-script.sh', [
            'path' => $path,
            'script' => $script,
        ]);
        $ssh->exec($command, 'run-script');

        info($command);

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

    public function cleanup(): void
    {
        $this->server->ssh()->exec(
            $this->getScript('cleanup.sh'),
            'cleanup'
        );
    }

    public function resourceInfo(): array
    {
        $info = $this->server->ssh()->exec(
            $this->getScript('resource-info.sh'),
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

    private function deleteTempFile(string $name): void
    {
        if (Storage::disk('local')->exists($name)) {
            Storage::disk('local')->delete($name);
        }
    }
}
