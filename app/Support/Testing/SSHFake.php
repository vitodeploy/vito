<?php

namespace App\Support\Testing;

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Helpers\SSH;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Assert;

class SSHFake extends SSH
{
    use ReflectsClosures;

    /** @var array<string> */
    protected array $commands = [];

    protected bool $connectionWillFail = false;

    protected bool $execWillFail = false;

    protected string $uploadedLocalPath;

    protected string $uploadedRemotePath;

    protected string $uploadedContent = '';

    public function __construct(protected ?string $output = null) {}

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

        return $this;
    }

    public function connectionWillFail(): void
    {
        $this->connectionWillFail = true;
    }

    public function execWillFail(): void
    {
        $this->execWillFail = true;
    }

    public function connect(bool $sftp = false): void
    {
        if ($this->connectionWillFail) {
            throw new SSHConnectionError('Connection failed');
        }
    }

    public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null, int $timeout = 0): string
    {
        if (! $this->log instanceof ServerLog && $log) {
            $serverLog = ServerLog::newLog($this->server, $log);
            if ($siteId !== null && $siteId !== 0) {
                $serverLog->forSite($siteId);
            }
            $serverLog->save();
            $this->log = $serverLog;
        }

        $this->commands[] = $command;

        if ($this->execWillFail) {
            throw new SSHCommandError(message: 'SSH command failed with an error', log: $this->log);
        }

        $output = $this->output ?? 'fake output';
        $this->writeOutput($output);

        if ($stream === true) {
            echo $output;
            ob_flush();
            flush();

            return '';
        }

        return $output;
    }

    public function upload(string $local, string $remote, ?string $owner = null, ?string $log = null, ?int $siteId = null, string $permission = '644'): void
    {
        $this->uploadedLocalPath = $local;
        $this->uploadedRemotePath = $remote;
        $this->uploadedContent = file_get_contents($local) ?: '';
    }

    public function download(string $local, string $remote, ?string $log = null, ?int $siteId = null): void {}

    /**
     * @param  array<string>|string  $commands
     */
    public function assertExecuted(array|string $commands): void
    {
        if ($this->commands === []) {
            Assert::fail('No commands are executed');
        }
        if (! is_array($commands)) {
            $commands = [$commands];
        }
        $allExecuted = true;
        foreach ($commands as $command) {
            if (! in_array($command, $commands)) {
                $allExecuted = false;
            }
        }
        if (! $allExecuted) {
            Assert::fail('The expected commands are not executed. executed commands: '.implode(', ', $this->commands));
        }
    }

    public function assertExecutedContains(string $command): void
    {
        if ($this->commands === []) {
            Assert::fail('No commands are executed');
        }
        $executed = false;
        foreach ($this->commands as $executedCommand) {
            if (str($executedCommand)->contains($command)) {
                $executed = true;
                break;
            }
        }

        Assert::assertTrue(
            $executed,
            'The expected command is not executed in the executed commands: '.implode(', ', $this->commands)
        );
    }

    public function assertNotExecutedContains(string $command, string $message = ''): void
    {
        $matches = array_filter(
            $this->commands,
            fn (string|View $executedCommand): bool => str((string) $executedCommand)->contains($command)
        );

        Assert::assertEmpty(
            $matches,
            $message ?: "The command '{$command}' should not be executed, but it was found in: ".implode(', ', $matches)
        );
    }

    public function assertFileUploaded(string $toPath, ?string $content = null): void
    {
        if ($this->uploadedLocalPath === '' || $this->uploadedLocalPath === '0' || ($this->uploadedRemotePath === '' || $this->uploadedRemotePath === '0')) {
            Assert::fail('File is not uploaded');
        }

        Assert::assertEquals($toPath, $this->uploadedRemotePath);

        if ($content !== null && $content !== '' && $content !== '0') {
            Assert::assertEquals($content, $this->uploadedContent);
        }
    }

    /**
     * @return array<int, string>
     */
    public function getExecutedCommands(): array
    {
        return array_map(fn (string|View $command): string => (string) $command, $this->commands);
    }

    public function getUploadedLocalPath(): string
    {
        return $this->uploadedLocalPath;
    }

    public function getUploadedContent(): string
    {
        return $this->uploadedContent;
    }
}
