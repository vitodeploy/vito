<?php

namespace App\SiteTypes\Concerns;

use App\Exceptions\SSHError;
use App\SSH\Mise\Mise;

trait UsesMiseRuntime
{
    /**
     * @var array<int, string>
     */
    public const SUPPORTED_NODE_VERSIONS = ['22', '23', '24'];

    /**
     * @var array<int, string>
     */
    public const SUPPORTED_BUN_VERSIONS = ['1.0', '1.1', '1.2'];

    /**
     * @return array<int, string>
     */
    public static function nodeVersionsWithNone(): array
    {
        return array_merge(['none'], self::SUPPORTED_NODE_VERSIONS);
    }

    /**
     * @return array<int, string>
     */
    public static function bunVersionsWithNone(): array
    {
        return array_merge(['none'], self::SUPPORTED_BUN_VERSIONS);
    }

    /**
     * @throws SSHError
     */
    protected function setupMiseRuntime(string $runtime, string $version): void
    {
        $mise = new Mise($this->site->server);

        $mise->ensureInstalled();

        $mise->installRuntime($this->site, $runtime, $version);
    }

    protected function miseShimsPath(): string
    {
        $user = $this->site->user ?? $this->site->server->getSshUser();

        return '/home/'.$user.'/.local/share/mise/shims';
    }

    /**
     * @return array<string, string>
     */
    protected function workerEnvironment(): array
    {
        return [
            'PATH' => $this->shimPath(),
        ];
    }

    protected function shimPath(): string
    {
        $user = $this->site->user ?? $this->site->server->getSshUser();

        return $this->miseShimsPath().':/usr/local/bin:/usr/bin:/bin:/home/'.$user.'/.local/bin';
    }

    protected function wrapCommand(string $command, bool $cdToSitePath = false): string
    {
        $cdPath = $cdToSitePath && $this->site->path ? 'cd '.$this->site->path.' && ' : '';

        $inner = 'export PATH='.$this->shimPath().' && '.$cdPath.$command;

        return 'bash -c '.escapeshellarg($inner);
    }
}
