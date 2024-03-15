<?php

namespace App\Helpers;

use App\Exceptions\SSHAuthenticationError;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Models\Server;
use App\Models\ServerLog;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use Throwable;

class SSH
{
    public Server $server;

    public ?ServerLog $log;

    protected SSH2|SFTP|null $connection = null;

    protected ?string $user;

    protected ?string $asUser;

    protected string $publicKey;

    protected PrivateKey $privateKey;

    public function init(Server $server, ?string $asUser = null): self
    {
        $this->connection = null;
        $this->log = null;
        $this->asUser = null;
        $this->server = $server->refresh();
        $this->user = $server->getSshUser();
        if ($asUser && $asUser != $server->getSshUser()) {
            $this->user = $asUser;
            $this->asUser = $asUser;
        }
        $this->privateKey = PublicKeyLoader::loadPrivateKey(
            file_get_contents($this->server->sshKey()['private_key_path'])
        );

        return $this;
    }

    public function setLog(string $logType, $siteId = null): self
    {
        $this->log = $this->server->logs()->create([
            'site_id' => $siteId,
            'name' => $this->server->id.'-'.strtotime('now').'-'.$logType.'.log',
            'type' => $logType,
            'disk' => config('core.logs_disk'),
        ]);

        return $this;
    }

    /**
     * @throws Throwable
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
     * @throws SSHCommandError
     * @throws SSHConnectionError
     */
    public function exec(string|array $commands, string $log = '', ?int $siteId = null): string
    {
        if ($log) {
            $this->setLog($log, $siteId);
        } else {
            $this->log = null;
        }

        try {
            if (! $this->connection) {
                $this->connect();
            }
        } catch (Throwable $e) {
            throw new SSHConnectionError($e->getMessage());
        }

        if (! is_array($commands)) {
            $commands = [$commands];
        }

        try {
            $result = '';
            foreach ($commands as $command) {
                $result .= $this->executeCommand($command);
            }

            return $result;
        } catch (Throwable $e) {
            throw new SSHCommandError($e->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function upload(string $local, string $remote): void
    {
        $this->log = null;

        if (! $this->connection) {
            $this->connect(true);
        }
        $this->connection->put($remote, $local, SFTP::SOURCE_LOCAL_FILE);
    }

    /**
     * @throws Exception
     */
    protected function executeCommand(string $command): string
    {
        if ($this->asUser) {
            $command = 'sudo su - '.$this->asUser.' -c '.'"'.addslashes($command).'"';
        }

        $this->connection->setTimeout(0);

        $output = $this->connection->exec($command);

        $this->log?->write($output);

        if (Str::contains($output, 'VITO_SSH_ERROR')) {
            throw new Exception('SSH command failed with an error');
        }

        return $output;
    }

    /**
     * @throws Exception
     */
    public function disconnect(): void
    {
        if ($this->connection) {
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
