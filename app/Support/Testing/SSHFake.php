<?php

namespace App\Support\Testing;

use App\Exceptions\SSHConnectionError;
use App\Helpers\SSH;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Assert;

class SSHFake extends SSH
{
    use ReflectsClosures;

    protected array $commands = [];

    protected ?string $output;

    protected bool $connectionWillFail = false;

    public function __construct(?string $output = null)
    {
        $this->output = $output;
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

    public function exec(string $command, string $log = '', ?int $siteId = null, ?bool $stream = false): string
    {
        if ($log) {
            $this->setLog($log, $siteId);
        } else {
            $this->log = null;
        }

        $this->commands[] = $command;

        $output = $this->output ?? 'fake output';
        $this->log?->write($output);

        if ($stream) {
            echo $output;
            ob_flush();
            flush();

            return '';
        }

        return $output;
    }

    public function upload(string $local, string $remote): void
    {
        $this->log = null;
    }

    public function assertExecuted(array|string $commands): void
    {
        if (! $this->commands) {
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
        Assert::assertTrue(true, $allExecuted);
    }

    public function assertExecutedContains(string $command): void
    {
        if (! $this->commands) {
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
        Assert::assertTrue(true, $executed);
    }
}
