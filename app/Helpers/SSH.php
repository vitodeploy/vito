<?php

namespace App\Helpers;

use App\Contracts\SSHCommand;
use App\Exceptions\SSHAuthenticationError;
use App\Exceptions\SSHConnectionError;
use App\Models\Server;
use App\Models\ServerLog;
use Exception;
use Illuminate\Support\Str;
use Throwable;

class SSH
{
    public Server $server;

    public ?ServerLog $log;

    protected mixed $connection;

    protected ?string $user;

    protected ?string $asUser;

    protected string $publicKey;

    protected string $privateKey;

    public function init(Server $server, string $asUser = null, bool $defaultKeys = false): self
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
        $this->publicKey = $this->server->sshKey($defaultKeys)['public_key_path'];
        $this->privateKey = $this->server->sshKey($defaultKeys)['private_key_path'];

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
    public function connect(): void
    {
        $defaultTimeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', 7);

        try {
            if (! ($this->connection = ssh2_connect($this->server->ip, $this->server->port))) {
                throw new SSHConnectionError('Cannot connect to the server');
            }

            if (! ssh2_auth_pubkey_file($this->connection, $this->user, $this->publicKey, $this->privateKey)) {
                throw new SSHAuthenticationError('Authentication failed');
            }
        } catch (Throwable $e) {
            ini_set('default_socket_timeout', $defaultTimeout);
            if ($this->server->status == 'ready') {
                $this->server->status = 'disconnected';
                $this->server->save();
            }
            throw $e;
        }

        ini_set('default_socket_timeout', $defaultTimeout);
    }

    /**
     * @throws Throwable
     */
    public function exec(string|array|SSHCommand $commands, string $log = '', int $siteId = null): string
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
        if (! $this->connection) {
            $this->connect();
        }

        $sftp = @ssh2_sftp($this->connection);
        if (! $sftp) {
            throw new Exception('Could not initialize SFTP');
        }

        $stream = @fopen("ssh2.sftp://$sftp$remote", 'w');

        if (! $stream) {
            throw new Exception("Could not open file: $remote");
        }

        $data_to_send = @file_get_contents($local);
        if ($data_to_send === false) {
            throw new Exception("Could not open local file: $local.");
        }

        if (@fwrite($stream, $data_to_send) === false) {
            throw new Exception("Could not send data from file: $local.");
        }

        @fclose($stream);
    }

    /**
     * @throws Exception
     */
    protected function executeCommand(string|SSHCommand $command): string
    {
        if ($command instanceof SSHCommand) {
            $commandContent = $command->content($this->server->os);
        } else {
            $commandContent = $command;
        }

        if ($this->asUser) {
            $commandContent = 'sudo su - '.$this->asUser.' -c '.'"'.addslashes($commandContent).'"';
        }

        if (! ($stream = ssh2_exec($this->connection, $commandContent, 'vt102', [], 100, 30))) {
            throw new Exception('SSH command failed');
        }

        $data = '';
        try {
            stream_set_blocking($stream, true);
            while ($buf = fread($stream, 1024)) {
                $data .= $buf;
                $this->log?->write($buf);
            }
            fclose($stream);
        } catch (Throwable) {
            $data = 'Error reading data';
        }

        if (Str::contains($data, 'VITO_SSH_ERROR')) {
            throw new Exception('SSH command failed with an error');
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            try {
                ssh2_disconnect($this->connection);
            } catch (Exception) {
                //
            }
            $this->connection = null;
        }
    }
}
