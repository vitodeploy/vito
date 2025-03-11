<?php

namespace App\Helpers;

use App\Exceptions\SSHAuthenticationError;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\ServerLog;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use Throwable;

class SSH
{
    public Server $server;

    public ?ServerLog $log = null;

    protected SSH2|SFTP|null $connection = null;

    protected ?string $user = null;

    protected ?string $asUser = null;

    protected string $publicKey;

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

    public function setLog(?ServerLog $log): self
    {
        $this->log = $log;

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

            assert($this->user !== null);

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
    public function exec(string $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null): string
    {
        if (! $this->log instanceof \App\Models\ServerLog && $log) {
            $this->log = ServerLog::newLog($this->server, $log);
            if ($siteId !== null && $siteId !== 0) {
                $this->log->forSite($siteId);
            }
            $this->log->save();
        }

        try {
            if (! $this->connection instanceof SSH2) {
                $this->connect();
                assert($this->connection instanceof SSH2);
            }
        } catch (Throwable $e) {
            throw new SSHConnectionError($e->getMessage());
        }

        try {
            if ($this->asUser !== null && $this->asUser !== '' && $this->asUser !== '0') {
                $command = addslashes($command);
                $command = str_replace('\\\'', '\'', $command);
                $command = 'sudo su - '.$this->asUser.' -c '.'"'.trim($command).'"';
            }

            $this->connection->setTimeout(0);
            if ($stream === true) {
                /** @var callable $streamCallback */
                $this->connection->exec($command, function ($output) use ($streamCallback) {
                    $this->log?->write($output);

                    return $streamCallback($output);
                });

                return '';
            }
            $output = '';
            $this->connection->exec($command, function (string $out) use (&$output): void {
                $this->log?->write($out);
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
            throw new SSHCommandError(
                message: $e->getMessage(),
                log: $this->log
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function upload(string $local, string $remote, ?string $owner = null): void
    {
        $this->log = null;

        if (! $this->connection instanceof SFTP) {
            $this->connect(true);
        }

        assert($this->connection instanceof SFTP);
        assert($this->user !== null);

        $tmpName = Str::random(10).strtotime('now');
        $tempPath = home_path($this->user).'/'.$tmpName;

        $this->connection->put($tempPath, $local, SFTP::SOURCE_LOCAL_FILE);

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
    public function download(string $local, string $remote): void
    {
        $this->log = null;

        if (! $this->connection instanceof SFTP) {
            $this->connect(true);
        }

        /** @phpstan-ignore-next-line */
        $this->connection->get($remote, $local);
    }

    /**
     * @throws SSHError
     */
    public function write(string $remotePath, string $content, ?string $owner = null): void
    {
        $tmpName = Str::random(10).strtotime('now');

        try {
            /** @var FilesystemAdapter $storageDisk */
            $storageDisk = Storage::disk('local');
            $storageDisk->put($tmpName, $content);
            $this->upload($storageDisk->path($tmpName), $remotePath, $owner);
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
        if ($this->connection instanceof \phpseclib3\Net\SSH2) {
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
