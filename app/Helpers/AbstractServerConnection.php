<?php

namespace App\Helpers;

use App\Contracts\ServerConnection;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Support\Facades\Storage;

abstract class AbstractServerConnection implements ServerConnection
{
    public Server $server;

    public ?ServerLog $log = null;

    protected string $user = '';

    protected ?string $asUser = null;

    protected ?string $logDisk = null;

    protected ?string $logPath = null;

    public function setLog(?ServerLog $log): self
    {
        $this->log = $log;

        return $this;
    }

    public function getLog(): ?ServerLog
    {
        return $this->log;
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
     * Ensure a server log exists when a log message is provided.
     */
    protected function ensureLog(?string $log, ?int $siteId = null): void
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
     * Write a chunk of output to either a file on a disk or the server log.
     */
    protected function writeOutput(string $chunk): void
    {
        if ($this->logDisk && $this->logPath) {
            Storage::disk($this->logDisk)->append($this->logPath, $chunk);
        } else {
            $this->log?->write($chunk);
        }
    }

    /**
     * Wrap a command to execute as a different user via sudo.
     */
    protected function wrapCommandForUser(string $command, ?string $defaultUser = null): string
    {
        $execUser = $this->asUser;
        if ($execUser === null || $execUser === '' || $execUser === '0') {
            $execUser = $defaultUser ?? $this->user;
        }

        if ($execUser && $execUser !== 'root') {
            return <<<BASH
            sudo -u {$execUser} bash <<'EOF'
            {$command}
            EOF
            BASH;
        }

        return $command;
    }

    /**
     * Determine the owner for file operations.
     */
    protected function resolveOwner(?string $owner): string
    {
        if ($owner === null || $owner === '' || $owner === '0') {
            return $this->user;
        }

        return $owner;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
