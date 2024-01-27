<?php

namespace App\Helpers;

use App\Contracts\SSHCommand;
use App\Exceptions\SSHAuthenticationError;
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

    protected SSH2|SFTP|null $connection;

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
        $this->user = $server->ssh_user;
        if ($asUser && $asUser != $server->ssh_user) {
            $this->user = $asUser;
            $this->asUser = $asUser;
        }
        $this->privateKey = PublicKeyLoader::loadPrivateKey(
            file_get_contents($this->server->sshKey()['private_key_path'])
        );

        return $this;
    }

    public function setLog(string $logType, $siteId = null): void
    {
        $this->log = $this->server->logs()->create([
            'site_id' => $siteId,
            'name' => $this->server->id.'-'.strtotime('now').'-'.$logType.'.log',
            'type' => $logType,
            'disk' => config('core.logs_disk'),
        ]);
    }

    /**
     * @throws Throwable
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
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function exec(string|array|SSHCommand $commands, string $log = '', ?int $siteId = null): string
    {
        if ($log) {
            $this->setLog($log, $siteId);
        } else {
            $this->log = null;
        }

        if (! $this->connection) {
            $this->connect();
        }

        if (! is_array($commands)) {
            $commands = [$commands];
        }

        $result = '';
        foreach ($commands as $command) {
            $result .= $this->executeCommand($command);
        }

        return $result;
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
    protected function executeCommand(string|SSHCommand $command): string
    {
        if ($command instanceof SSHCommand) {
            $commandContent = $command->content();
        } else {
            $commandContent = $command;
        }

        if ($this->asUser) {
            $commandContent = 'sudo su - '.$this->asUser.' -c '.'"'.addslashes($commandContent).'"';
        }

        $output = $this->connection->exec($commandContent);

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
}
