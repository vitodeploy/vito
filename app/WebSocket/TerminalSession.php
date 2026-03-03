<?php

namespace App\WebSocket;

use App\Models\Server;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use React\EventLoop\LoopInterface;

class TerminalSession
{
    protected SSH2 $ssh;

    protected bool $closed = false;

    /**
     * @param  callable(string): void  $onOutput
     * @param  callable(): void  $onClose
     */
    public function __construct(
        protected Server $server,
        protected string $sshUser,
        protected LoopInterface $loop,
        protected mixed $onOutput,
        protected mixed $onClose,
        protected int $initialCols = 80,
        protected int $initialRows = 24,
    ) {}

    /** @throws \RuntimeException */
    public function connect(): void
    {
        $ip = $this->server->ip;
        if (str($ip)->contains(':')) {
            $ip = '['.$ip.']';
        }

        $this->ssh = new SSH2($ip, $this->server->port);
        $this->ssh->setTimeout(0);

        $privateKey = PublicKeyLoader::loadPrivateKey(
            (string) file_get_contents($this->server->sshKey()['private_key_path'])
        );

        if (! $this->ssh->login($this->sshUser, $privateKey)) {
            throw new \RuntimeException('SSH authentication failed for user: '.$this->sshUser);
        }

        $this->ssh->setTerminal('xterm-256color');
        $this->ssh->setWindowSize($this->initialCols, $this->initialRows);
        $this->ssh->openShell();

        $this->loop->addReadStream($this->ssh->fsock, function ($stream): void {
            if ($this->closed) {
                return;
            }

            try {
                $this->ssh->setTimeout(0.001);
                $data = $this->ssh->read('', SSH2::READ_NEXT);

                if (is_string($data) && strlen($data) > 0) {
                    ($this->onOutput)($data);
                }

                if ($data === true && ! $this->ssh->isTimeout()) {
                    $this->close();
                }
            } catch (\Throwable $e) {
                Log::error('Terminal read error', ['error' => $e->getMessage()]);
                $this->close();
            }
        });
    }

    public function write(string $data): void
    {
        if ($this->closed) {
            return;
        }

        try {
            $this->ssh->write($data);
        } catch (\Throwable $e) {
            Log::error('Terminal write error', ['error' => $e->getMessage()]);
            $this->close();
        }
    }

    public function resize(int $cols, int $rows): void
    {
        if ($this->closed) {
            return;
        }

        try {
            $this->ssh->setWindowSize($cols, $rows);
        } catch (\Throwable $e) {
            Log::error('Terminal resize error', ['error' => $e->getMessage()]);
        }
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        try {
            if (isset($this->ssh->fsock) && is_resource($this->ssh->fsock)) {
                $this->loop->removeReadStream($this->ssh->fsock);
            }
        } catch (\Throwable) {
        }

        try {
            $this->ssh->disconnect();
        } catch (\Throwable) {
        }

        ($this->onClose)();
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }
}
