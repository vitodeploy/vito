<?php

namespace App\Helpers;

use App\Exceptions\SSHAuthenticationError;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Exceptions\SSHError;
use App\Models\Server;
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

class SSH extends AbstractServerConnection
{
    protected SSH2|SFTP|null $connection = null;

    protected PrivateKey $privateKey;

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
     * @throws SSHConnectionError
     */
    public function connect(bool $sftp = false): void
    {
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
            $commandStr = (string) $command;
            if ($this->asUser !== null && $this->asUser !== '' && $this->asUser !== '0') {
                $commandStr = $this->wrapCommandForUser($commandStr, $this->asUser);
            }

            $this->connection->setTimeout(0);
            if ($stream === true) {
                /** @var callable $streamCallback */
                $this->connection->exec($commandStr, function ($output) use ($streamCallback) {
                    $this->writeOutput($output);

                    return $streamCallback($output);
                });

                return '';
            }
            $output = '';
            $this->connection->exec($commandStr, function (string $out) use (&$output): void {
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

        $owner = $this->resolveOwner($owner);
        $this->exec(sprintf('sudo mv %s %s && sudo chown %s:%s %s && sudo chmod 644 %s',
            $tempPath, $remote, $owner, $owner, $remote, $remote
        ));
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

    public function disconnect(): void
    {
        if ($this->connection instanceof SSH2) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }
}
