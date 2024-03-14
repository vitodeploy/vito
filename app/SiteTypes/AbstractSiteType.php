<?php

namespace App\SiteTypes;

use App\Exceptions\SourceControlIsNotConnected;
use App\Models\Site;

abstract class AbstractSiteType implements SiteType
{
    protected Site $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    protected function progress(int $percentage): void
    {
        $this->site->progress = $percentage;
        $this->site->save();
    }

    /**
     * @throws SourceControlIsNotConnected
     */
    protected function deployKey(): void
    {
        $os = $this->site->server->os();
        $os->generateSSHKey($this->site->getSshKeyName());
        $this->site->ssh_key = $os->readSSHKey($this->site->getSshKeyName());
        $this->site->save();
        $this->site->sourceControl()->provider()->deployKey(
            $this->site->domain.'-key-'.$this->site->id,
            $this->site->repository,
            $this->site->ssh_key
        );
    }
}
