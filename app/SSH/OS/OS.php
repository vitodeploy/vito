<?php

namespace App\SSH\OS;

use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Site;
use RuntimeException;

class OS
{
    public const FILE_NOT_FOUND = 'VITO_NO_FILE';

    private const SHELL_IDENTIFIER = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public function __construct(protected Server $server) {}

    private function assertShellIdentifier(string $name): void
    {
        if (preg_match(self::SHELL_IDENTIFIER, $name) !== 1) {
            throw new RuntimeException('Refusing to emit shell statement with unsafe identifier.');
        }
    }

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
    public function upgradeKernel(): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.upgrade-kernel'),
            'upgrade-kernel'
        );
    }

    /**
     * @return array{updates: int, kernel: int}
     *
     * @throws SSHError
     */
    public function availableUpdates(): array
    {
        $result = $this->server->ssh()->exec(
            view('ssh.os.available-updates'),
            'check-available-updates'
        );

        return [
            'updates' => max(str($result)->after('Available updates:')->trim()->toInteger(), 0),
            'kernel' => max(str($result)->after('Kernel updates:')->trim()->toInteger(), 0),
        ];
    }

    /**
     * @throws SSHError
     */
    public function createUser(string $user, string $password, string $key, bool $clearKeys = false): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.create-user', [
                'user' => $user,
                'password' => $password,
                'key' => $key,
                'clearKeys' => $clearKeys,
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
                'key' => escapeshellarg(trim($this->server->sshKey()['public_key'])),
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
            ]),
            'get-public-key'
        );
    }

    /**
     * @throws SSHError
     */
    public function deploySSHKey(string $key, string $user): void
    {
        $this->server->ssh($user)->exec(
            view('ssh.os.deploy-ssh-key', [
                'key' => $key,
            ]),
            'deploy-ssh-key'
        );
    }

    /**
     * @throws SSHError
     */
    public function deleteSSHKey(string $key, string $user): void
    {
        $this->server->ssh($user)->exec(
            view('ssh.os.delete-ssh-key', [
                'key' => $key,
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
            'read-ssh-key'
        );
    }

    /**
     * @throws SSHError
     */
    public function reboot(): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.reboot'),
            'reboot'
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
    public function runScript(
        string $path,
        string $script,
        ?ServerLog $serverLog,
        ?string $user = null,
        ?array $variables = [],
        ?array $aliases = []
    ): ServerLog {
        $ssh = $this->server->ssh($user);
        if ($serverLog instanceof ServerLog) {
            $ssh->setLog($serverLog);
        }
        $command = "set -e\n";
        $command .= "set -o pipefail\n";
        $command .= "shopt -s expand_aliases\n";
        if ($aliases !== null && $aliases !== []) {
            foreach ($aliases as $key => $alias) {
                $this->assertShellIdentifier((string) $key);
                $command .= sprintf("alias %s=%s\n", $key, escapeshellarg((string) $alias));
            }
        }
        if ($variables !== null && $variables !== []) {
            foreach ($variables as $key => $variable) {
                $this->assertShellIdentifier((string) $key);
                $command .= sprintf("export %s=%s\n", $key, escapeshellarg((string) $variable));
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
            ]),
            'download'
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
     * @return array<string, string|bool|null>
     *
     * @throws SSHError
     */
    public function resourceInfo(): array
    {
        $info = $this->server->ssh()->exec(
            command: view('ssh.os.resource-info'),
            timeout: 5,
        );

        $values = [];
        foreach (preg_split('/\R/', $info) ?: [] as $line) {
            if (preg_match('/^([a-z_]+):(.*)$/', trim($line), $matches) === 1) {
                $values[$matches[1]] = trim($matches[2]);
            }
        }

        $nullIfEmpty = fn (string $key): ?string => ($values[$key] ?? '') === '' ? null : $values[$key];

        [$cpuUsage, $cpuSteal] = array_pad(explode('|', $values['cpu_usage_and_steal'] ?? ''), 2, '');

        return [
            'load' => $values['load'] ?? '',
            'memory_total' => $values['memory_total'] ?? '',
            'memory_used' => $values['memory_used'] ?? '',
            'memory_free' => $values['memory_free'] ?? '',
            'disk_total' => $values['disk_total'] ?? '',
            'disk_used' => $values['disk_used'] ?? '',
            'disk_free' => $values['disk_free'] ?? '',
            'cpu_cores' => $nullIfEmpty('cpu_cores'),
            'cpu_physical_cores' => $nullIfEmpty('cpu_physical_cores'),
            'cpu_usage_percent' => $cpuUsage === '' ? null : $cpuUsage,
            'cpu_per_core_usage_percent' => null,
            'cpu_steal_percent' => $cpuSteal === '' ? null : $cpuSteal,
            'swap_total' => $nullIfEmpty('swap_total'),
            'swap_used' => $nullIfEmpty('swap_used'),
            'swap_free' => $nullIfEmpty('swap_free'),
            'swap_used_percent' => $nullIfEmpty('swap_used_percent'),
            'oom_kill_count' => $nullIfEmpty('oom_kill_count'),
            'uptime_seconds' => $nullIfEmpty('uptime_seconds'),
            'reboot_required' => ($values['reboot_required'] ?? '0') === '1',
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
            $user,
            'write-file'
        );
    }

    /**
     * @throws SSHError
     */
    public function mkdir(string $path, ?string $user = null): string
    {
        return $this->server->ssh($user)->exec('mkdir -p '.$path);
    }

    /**
     * @throws SSHError
     */
    public function compress(string $sourcePath, string $zipPath): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.compress', [
                'sourcePath' => $sourcePath,
                'zipPath' => $zipPath,
            ]),
            'compress'
        );
    }

    /**
     * @throws SSHError
     */
    public function extractArchive(string $backupPath, string $restorePath, ?string $owner = null, ?string $permissions = null): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.extract-archive', [
                'backupPath' => $backupPath,
                'restorePath' => $restorePath,
                'owner' => $owner,
                'permissions' => $permissions,
            ]),
            'extract-archive'
        );
    }

    /**
     * Clear a remote log file while preserving permissions and ownership
     *
     * @throws SSHError
     */
    public function clearFile(string $path): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.clear-file', [
                'path' => $path,
            ]),
            'clear-file'
        );
    }
}
