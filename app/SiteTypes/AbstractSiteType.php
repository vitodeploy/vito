<?php

namespace App\SiteTypes;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Service;
use App\Models\Site;
use App\Services\PHP\PHP;
use Illuminate\Support\Str;
use RuntimeException;

abstract class AbstractSiteType implements SiteType
{
    public function __construct(protected Site $site) {}

    abstract public static function make(): self;

    public function createRules(array $input): array
    {
        return [];
    }

    public function createFields(array $input): array
    {
        return [];
    }

    public function data(array $input): array
    {
        return [];
    }

    public function baseCommands(): array
    {
        return [];
    }

    protected function progress(int $percentage): void
    {
        $this->site->progress = $percentage;
        $this->site->save();
    }

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    protected function deployKey(): void
    {
        $os = $this->site->server->os();
        $os->generateSSHKey($this->site->getSshKeyName(), $this->site);
        $this->site->ssh_key = $os->readSSHKey($this->site->getSshKeyName(), $this->site);
        $this->site->save();
        $this->site->sourceControl?->provider()?->deployKey(
            $this->site->domain.'-key-'.$this->site->id,
            $this->site->repository,
            $this->site->ssh_key
        );
    }

    /**
     * @throws SSHError
     */
    protected function isolate(): void
    {
        if (! $this->site->isIsolated()) {
            return;
        }

        $this->site->server->os()->createIsolatedUser(
            $this->site->user,
            Str::random(15),
            $this->site->id
        );

        // Generate the FPM pool
        if ($this->site->php_version) {
            $service = $this->site->php();
            if (! $service instanceof Service) {
                throw new RuntimeException('PHP service not found');
            }
            /** @var PHP $php */
            $php = $service->handler();
            $php->createFpmPool(
                $this->site->user,
                $this->site->php_version
            );
        }
    }
}
