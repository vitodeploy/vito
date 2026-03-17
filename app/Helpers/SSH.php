<?php

namespace App\Helpers;

use App\Exceptions\SSHAuthenticationError;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\ServerLog;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use RuntimeException;
use Throwable;

class SSH
{
    public Server $server;

    public ?ServerLog $log = null;

    protected SSH2|SFTP|null $connection = null;

    protected string $user = '';

    protected ?string $asUser = null;

    protected string $publicKey;

    protected PrivateKey $privateKey;

    protected ?string $logDisk = null;

    protected ?string $logPath = null;

    public function init(Server $server, ?string $asUser = null): self
    {
        $this->connection = null;
        $this->log = null;
        $this->asUser = null;
        $this->server = $server->refresh();
        $this->user = $server->getSshUser();
        if ($asUser && $asUser !== $server->getSshUser()) {
            $this->asUser = $asUser;
        }
        $this->privateKey = PublicKeyLoader::loadPrivateKey(
            (string) file_get_contents($this->server->sshKey()['private_key_path'])
        );

        return $this;
    }

    /**
     * Ensure a server log exists when a log message is provided.
     */
    private function ensureLog(?string $log, ?int $siteId = null): void
    {
        if (! $this->log instanceof ServerLog && $log && ! $this->logDisk && ! $this->logPath) {
            $this->log = ServerLog::newLog($this->server, $log);
            if ($siteId !== null && $siteId !== 0) {
                $this->log->forSite($siteId);
            }
            $this->log->save();
        }
    }

    /**
     * Ensure there is an active SFTP connection and return it.
     */
    private function ensureSftp(): SFTP
    {
        if (! $this->connection instanceof SFTP) {
            $this->connect(true);
        }

        if (! $this->connection instanceof SFTP) {
            throw new RuntimeException('Connection is not established!');
        }

        return $this->connection;
    }

    /**
     * Write a chunk of output to either a file on a disk or the server log.
     */
    private function writeOutput(string $chunk): void
    {
        if ($this->logDisk && $this->logPath) {
            Storage::disk($this->logDisk)->append($this->logPath, $chunk);
        } else {
            $this->log?->write($chunk);
        }
    }

    public function setLog(?ServerLog $log): self
    {
        $this->log = $log;

        return $this;
    }

    public function useLog(string $disk, string $path): self
    {
        $this->logDisk = $disk;
        $this->logPath = $path;

        return $this;
    }

    public function asUser(?string $user): self
    {
        $this->asUser = $user;

        return $this;
    }

    /**
     * @throws SSHConnectionError
     */
    public function connect(bool $sftp = false): void
    {
        // If the IP is an IPv6 address, we need to wrap it in square brackets
        $ip = $this->server->ip;
        if (str($ip)->contains(':')) {
            $ip = '['.$ip.']';
        }
        try {
            if ($sftp) {
                $this->connection = new SFTP($ip, $this->server->port);
            } else {
                $this->connection = new SSH2($ip, $this->server->port);
            }

            $login = $this->connection->login($this->user, $this->privateKey);

            if (! $login) {
                throw new SSHAuthenticationError('Error authenticating');
            }
        } catch (Throwable $e) {
            Log::error('Error connecting', [
                'msg' => $e->getMessage(),
            ]);
            throw new SSHConnectionError($e->getMessage());
        }
    }

    /**
     * @throws SSHError
     */
    public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null): string
    {
        $this->ensureLog($log, $siteId);

        try {
            if (! $this->connection instanceof SSH2) {
                $this->connect();
            }
        } catch (Throwable $e) {
            $this->writeOutput($e->getMessage());
            throw new SSHConnectionError($e->getMessage());
        }

        try {
            if ($this->asUser !== null && $this->asUser !== '' && $this->asUser !== '0') {
                $command = <<<BASH
                sudo -u {$this->asUser} bash <<'EOF'
                cd ~ || { echo 'VITO_SSH_ERROR: failed to cd to home directory' >&2; exit 1; }
                {$command}
                EOF
                BASH;
            }

            $this->connection->setTimeout(0);
            if ($stream === true) {
                /** @var callable $streamCallback */
                $this->connection->exec($command, function ($output) use ($streamCallback) {
                    $this->writeOutput($output);

                    return $streamCallback($output);
                });

                return '';
            }
            $output = '';
            $this->connection->exec($command, function (string $out) use (&$output): void {
                $this->writeOutput($out);

                $output .= $out;
            });
            if ($this->connection->getExitStatus() !== 0 || Str::contains($output, 'VITO_SSH_ERROR')) {
                throw new SSHCommandError(
                    message: 'SSH command failed with an error',
                    log: $this->log
                );
            }

            return $output;
        } catch (Throwable $e) {
            Log::error('Error executing command', [
                'msg' => $e->getMessage(),
                'log' => $this->log,
            ]);
            $this->writeOutput($e->getMessage());
            throw new SSHCommandError(
                message: $e->getMessage(),
                log: $this->log
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function upload(string $local, string $remote, ?string $owner = null, ?string $log = null, ?int $siteId = null): void
    {
        $this->ensureLog($log, $siteId);
        $sftp = $this->ensureSftp();

        $tmpName = Str::random(10).strtotime('now');
        $tempPath = home_path($this->user).'/'.$tmpName;

        $sftp->put($tempPath, $local, SFTP::SOURCE_LOCAL_FILE);

        $this->exec(sprintf('sudo mv %s %s', $tempPath, $remote));
        if ($owner === null || $owner === '' || $owner === '0') {
            $owner = $this->user;
        }
        $this->exec(sprintf('sudo chown %s:%s %s', $owner, $owner, $remote));
        $this->exec(sprintf('sudo chmod 644 %s', $remote));
    }

    /**
     * @throws Throwable
     */
    public function download(string $local, string $remote, ?string $log = null, ?int $siteId = null): void
    {
        $this->ensureLog($log, $siteId);
        $sftp = $this->ensureSftp();

        $sftp->get($remote, $local);
    }

    /**
     * @throws SSHError
     */
    public function write(string $remotePath, string|View $content, ?string $owner = null, ?string $log = null, ?int $siteId = null): void
    {
        $tmpName = Str::random(10).strtotime('now');

        try {
            /** @var FilesystemAdapter $storageDisk */
            $storageDisk = Storage::disk('local');
            $storageDisk->put($tmpName, $content);
            $tmpRemotePath = '/tmp/'.$tmpName;
            $this->upload($storageDisk->path($tmpName), $tmpRemotePath, $owner, $log, $siteId);
            $this->asUser($owner)->exec('cat '.$tmpRemotePath.' > '.$remotePath);
        } catch (Throwable $e) {
            throw new SSHCommandError(
                message: $e->getMessage()
            );
        } finally {
            if (Storage::disk('local')->exists($tmpName)) {
                Storage::disk('local')->delete($tmpName);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function disconnect(): void
    {
        if ($this->connection instanceof SSH2) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
