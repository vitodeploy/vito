<?php

namespace App\Support\Testing;

use App\Exceptions\SSHConnectionError;
use App\Helpers\SSH;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Assert;

class SSHFake extends SSH
{
    use ReflectsClosures;

    /** @var array<string> */
    protected array $commands = [];

    protected bool $connectionWillFail = false;

    protected string $uploadedLocalPath;

    protected string $uploadedRemotePath;

    protected string $uploadedContent;

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

    public function connect(bool $sftp = false): void
    {
        if ($this->connectionWillFail) {
            throw new SSHConnectionError('Connection failed');
        }
    }

    public function exec(string $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null): string
    {
        if (! $this->log instanceof ServerLog && $log) {
            /** @var ServerLog $log */
            $log = $this->server->logs()->create([
                'site_id' => $siteId,
                'name' => $this->server->id.'-'.strtotime('now').'-'.$log.'.log',
                'type' => $log,
                'disk' => config('core.logs_disk'),
            ]);
            $this->log = $log;
        }

        $this->commands[] = $command;

        $output = $this->output ?? 'fake output';
        $this->log?->write($output);

        if ($stream === true) {
            echo $output;
            ob_flush();
            flush();

            return '';
        }

        return $output;
    }

    public function upload(string $local, string $remote, ?string $owner = null): void
    {
        $this->uploadedLocalPath = $local;
        $this->uploadedRemotePath = $remote;
        $this->uploadedContent = file_get_contents($local) ?: '';
        $this->log = null;
    }

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
        if (! $executed) {
            Assert::fail(
                'The expected command is not executed in the executed commands: '.implode(', ', $this->commands)
            );
        }
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

    public function getUploadedLocalPath(): string
    {
        return $this->uploadedLocalPath;
    }
}
