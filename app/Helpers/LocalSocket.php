<?php

namespace App\Helpers;

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Exceptions\SSHError;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class LocalSocket extends AbstractServerConnection
{
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

    /**
     * Connection is handled per-command for local sockets.
     */
    public function connect(bool $sftp = false): void
    {
        // No persistent connection needed for local sockets
        // Connection is established per-command in exec()
    }

    /**
     * Get the socket path.
     */
    protected function getSocketPath(): string
    {
        return '/run/vito-root.sock';
    }

    /**
     * Create a socket connection.
     *
     * @return resource
     *
     * @throws SSHConnectionError
     */
    protected function createSocket(string $context = 'operation')
    {
        $socketPath = $this->getSocketPath();

        $sock = @stream_socket_client("unix://{$socketPath}", $errno, $errstr, 5);

        if (! $sock) {
            Log::error("Error connecting to local socket for {$context}", [
                'socket' => $socketPath,
                'errno' => $errno,
                'errstr' => $errstr,
            ]);
            throw new SSHConnectionError("Failed to connect to local socket {$socketPath}: {$errstr} (errno: {$errno})");
        }

        return $sock;
    }

    /**
     * Safely close a socket resource.
     *
     * @param  resource|null  $sock
     */
    protected function closeSocket($sock): void
    {
        if (is_resource($sock)) {
            fclose($sock);
        }
    }

    /**
     * Send an action request and get a single response.
     *
     * @return array<string, mixed>|null
     */
    protected function sendAction(string $action, string $expectedType): ?array
    {
        $sock = $this->createSocket($action);

        try {
            fwrite($sock, json_encode(['action' => $action])."\n");

            $line = fgets($sock);
            $msg = json_decode(trim($line), true);

            $this->closeSocket($sock);

            if (is_array($msg) && ($msg['type'] ?? null) === $expectedType) {
                return $msg;
            }

            return null;
        } catch (Throwable $e) {
            $this->closeSocket($sock);
            throw $e;
        }
    }

    /**
     * @throws SSHError
     */
    public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null): string
    {
        $this->ensureLog($log, $siteId);

        try {
            $sock = $this->createSocket('command execution');
        } catch (SSHConnectionError $e) {
            $this->writeOutput($e->getMessage());
            throw $e;
        }

        try {
            $commandStr = $this->wrapCommandForUser((string) $command, 'vito');

            // Build and send the request
            $request = [
                'command' => $commandStr,
                'cwd' => '/tmp',
            ];
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

            $this->closeSocket($sock);

            if ($exitCode !== 0 || Str::contains($output, 'VITO_SSH_ERROR')) {
                throw new SSHCommandError(
                    message: 'Command failed with exit code: '.$exitCode,
                    log: $this->log
                );
            }

            return $stream === true ? '' : $output;
        } catch (SSHError $e) {
            $this->closeSocket($sock);
            throw $e;
        } catch (Throwable $e) {
            $this->closeSocket($sock);
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

        $content = file_get_contents($local);
        if ($content === false) {
            throw new RuntimeException("Failed to read local file: {$local}");
        }

        $base64Content = base64_encode($content);
        $tmpPath = '/tmp/'.Str::random(10).strtotime('now');
        $owner = $this->resolveOwner($owner);

        // Combine all operations into a single command
        $this->exec(<<<BASH
            echo '{$base64Content}' | base64 -d > {$tmpPath} && \
            sudo mv {$tmpPath} {$remote} && \
            sudo chown {$owner}:{$owner} {$remote} && \
            sudo chmod 644 {$remote}
            BASH);
    }

    /**
     * @throws Throwable
     */
    public function download(string $local, string $remote, ?string $log = null, ?int $siteId = null): void
    {
        $this->ensureLog($log, $siteId);

        $base64Content = $this->exec("base64 {$remote}");

        $content = base64_decode($base64Content, true);
        if (! is_string($content)) {
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

    /**
     * Get the current version from the local socket service.
     *
     * @throws SSHConnectionError
     */
    public function getVersion(): string
    {
        try {
            $msg = $this->sendAction('version', 'version');

            return $msg['current_version'] ?? 'Unknown';
        } catch (Throwable $e) {
            Log::error('Error getting version from local socket', [
                'msg' => $e->getMessage(),
            ]);

            return 'Unknown';
        }
    }

    /**
     * Check if an update is available for the local socket service.
     *
     * @return array{status: string, current_version?: string, latest_version?: string, message?: string}
     *
     * @throws SSHConnectionError
     */
    public function checkUpdate(): array
    {
        try {
            $msg = $this->sendAction('check-update', 'update');

            if ($msg === null) {
                return ['status' => 'failed', 'message' => 'Unexpected response from socket'];
            }

            return [
                'status' => $msg['update_status'] ?? 'failed',
                'current_version' => $msg['current_version'] ?? null,
                'latest_version' => $msg['latest_version'] ?? null,
                'message' => $msg['message'] ?? null,
            ];
        } catch (Throwable $e) {
            Log::error('Error checking update from local socket', [
                'msg' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Perform an update (downloads, installs, and restarts the service).
     *
     * @param  callable|null  $progressCallback  Optional callback for progress updates: function(string $status, array $msg)
     * @return array{status: string, message?: string}
     *
     * @throws SSHConnectionError
     */
    public function performUpdate(?callable $progressCallback = null): array
    {
        $sock = $this->createSocket('update');

        try {
            fwrite($sock, json_encode(['action' => 'update'])."\n");

            $finalStatus = 'failed';
            $finalMessage = null;

            while ($line = fgets($sock)) {
                $msg = json_decode(trim($line), true);

                if (! is_array($msg) || ($msg['type'] ?? null) !== 'update') {
                    continue;
                }

                $status = $msg['update_status'] ?? 'failed';

                if ($progressCallback !== null) {
                    $progressCallback($status, $msg);
                }

                $finalStatus = $status;
                if ($status === 'failed') {
                    $finalMessage = $msg['message'] ?? 'Update failed';
                }

                if (in_array($status, ['current', 'restarting', 'failed'])) {
                    break;
                }
            }

            $this->closeSocket($sock);

            return [
                'status' => $finalStatus,
                'message' => $finalMessage,
            ];
        } catch (Throwable $e) {
            $this->closeSocket($sock);
            Log::error('Error performing update from local socket', [
                'msg' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }
}
