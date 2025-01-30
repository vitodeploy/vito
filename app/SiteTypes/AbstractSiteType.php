<?php

namespace App\SiteTypes;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\SSH\Services\PHP\PHP;
use Illuminate\Support\Str;

abstract class AbstractSiteType implements SiteType
{
    protected Site $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

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

    public function editRules(array $input): array
    {
        return $this->createRules($input);
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
            /** @var PHP $php */
            $php = $this->site->php()->handler();
            $php->createFpmPool(
                $this->site->user,
                $this->site->php_version,
                $this->site->id
            );
        }
    }
}
