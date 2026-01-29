<?php

namespace App\Helpers;

use App\Contracts\ServerConnection;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class LocalSocket implements ServerConnection
{
    public Server $server;

    public ?ServerLog $log = null;

    protected string $user = '';

    protected ?string $asUser = null;

    protected ?string $logDisk = null;

    protected ?string $logPath = null;

    public function init(Server $server, ?string $asUser = null): self
    {
        $this->log = null;
        $this->asUser = null;
        $this->server = $server->refresh();
        $this->user = $server->getSshUser();
        if ($asUser && $asUser !== $server->getSshUser()) {
            $this->asUser = $asUser;
        }

        return $this;
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
     * Connection is handled per-command for local sockets.
     */
    public function connect(bool $sftp = false): void
    {
        // No persistent connection needed for local sockets
        // Connection is established per-command in exec()
    }

    /**
     * Get the socket path based on the user.
     */
    protected function getSocketPath(): string
    {
        $socketUser = $this->asUser ?? 'vito';

        return "/run/vito-{$socketUser}.sock";
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

    /**
     * @throws SSHError
     */
    public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null): string
    {
        $this->ensureLog($log, $siteId);

        $socketPath = $this->getSocketPath();

        try {
            $sock = @stream_socket_client("unix://{$socketPath}", $errno, $errstr, 5);

            if (! $sock) {
                throw new SSHConnectionError("Failed to connect to local socket {$socketPath}: {$errstr} (errno: {$errno})");
            }
        } catch (Throwable $e) {
            Log::error('Error connecting to local socket', [
                'msg' => $e->getMessage(),
                'socket' => $socketPath,
            ]);
            $this->writeOutput($e->getMessage());
            throw new SSHConnectionError($e->getMessage());
        }

        try {
            $commandStr = (string) $command;

            // Build the request
            $request = [
                'command' => $commandStr,
            ];

            // Send the request
            fwrite($sock, json_encode($request)."\n");

            $output = '';
            $exitCode = 0;

            // Read the NDJSON response stream
            while ($line = fgets($sock)) {
                $msg = json_decode(trim($line), true);

                if (! is_array($msg) || ! isset($msg['type'])) {
                    continue;
                }

                switch ($msg['type']) {
                    case 'stdout':
                    case 'stderr':
                        $data = $msg['data'] ?? '';
                        $this->writeOutput($data);

                        if ($stream === true && $streamCallback !== null) {
                            $result = $streamCallback($data);
                            if ($result === false) {
                                break 2;
                            }
                        } else {
                            $output .= $data;
                        }
                        break;

                    case 'exit':
                        $exitCode = $msg['code'] ?? 0;
                        break 2;

                    case 'error':
                        $errorMessage = $msg['message'] ?? 'Unknown error';
                        $this->writeOutput($errorMessage);
                        throw new SSHCommandError(
                            message: $errorMessage,
                            log: $this->log
                        );
                }
            }

            fclose($sock);

            if ($exitCode !== 0 || Str::contains($output, 'VITO_SSH_ERROR')) {
                throw new SSHCommandError(
                    message: 'Command failed with exit code: '.$exitCode,
                    log: $this->log
                );
            }

            if ($stream === true) {
                return '';
            }

            return $output;
        } catch (SSHError $e) {
            if (is_resource($sock)) {
                fclose($sock);
            }
            throw $e;
        } catch (Throwable $e) {
            if (is_resource($sock)) {
                fclose($sock);
            }
            Log::error('Error executing command via local socket', [
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

        // Read the local file content and encode it in base64
        $content = file_get_contents($local);
        if ($content === false) {
            throw new RuntimeException("Failed to read local file: {$local}");
        }

        $base64Content = base64_encode($content);
        $tmpPath = '/tmp/'.Str::random(10).strtotime('now');

        // Write content via base64 decode to handle binary files
        $this->exec("echo '{$base64Content}' | base64 -d > {$tmpPath}");
        $this->exec("sudo mv {$tmpPath} {$remote}");

        if ($owner === null || $owner === '' || $owner === '0') {
            $owner = $this->user;
        }
        $this->exec("sudo chown {$owner}:{$owner} {$remote}");
        $this->exec("sudo chmod 644 {$remote}");
    }

    /**
     * @throws Throwable
     */
    public function download(string $local, string $remote, ?string $log = null, ?int $siteId = null): void
    {
        $this->ensureLog($log, $siteId);

        // Read the remote file via base64 to handle binary files
        $base64Content = $this->exec("base64 {$remote}");

        $content = base64_decode($base64Content);
        if ($content === false) {
            throw new RuntimeException("Failed to decode file content from: {$remote}");
        }

        $result = file_put_contents($local, $content);
        if ($result === false) {
            throw new RuntimeException("Failed to write local file: {$local}");
        }
    }

    /**
     * @throws SSHError
     */
    public function write(string $remotePath, string|View $content, ?string $owner = null, ?string $log = null, ?int $siteId = null): void
    {
        $tmpName = Str::random(10).strtotime('now');

        try {
            Storage::disk('local')->put($tmpName, $content);
            $localPath = Storage::disk('local')->path($tmpName);

            $this->upload($localPath, $remotePath, $owner, $log, $siteId);
        } finally {
            if (Storage::disk('local')->exists($tmpName)) {
                Storage::disk('local')->delete($tmpName);
            }
        }
    }

    public function disconnect(): void
    {
        // No persistent connection to disconnect
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
