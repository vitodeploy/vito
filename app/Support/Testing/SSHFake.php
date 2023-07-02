<?php

namespace App\Support\Testing;

use App\Contracts\SSHCommand;
use App\Models\Server;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Assert as PHPUnit;

class SSHFake
{
    use ReflectsClosures;

    protected array $commands;

    protected string $output = '';

    public function init(Server $server, string $asUser = null): self
    {
        return $this;
    }

    public function outputShouldBe(string $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function assertExecuted(array|string $commands): void
    {
        if (! $this->commands) {
            PHPUnit::fail('No commands are executed');
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
            PHPUnit::fail('The expected commands are not executed');
        }
        PHPUnit::assertTrue(true, $allExecuted);
    }

    public function exec(string|array|SSHCommand $commands, string $log = '', int $siteId = null): string
    {
        if (! is_array($commands)) {
            $commands = [$commands];
        }

        foreach ($commands as $command) {
            if (is_string($command)) {
                $this->commands[] = $command;
            } else {
                $this->commands[] = get_class($command);
            }
        }

        return 'fake output';
    }
}
