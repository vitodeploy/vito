<?php

namespace App\Web\Traits;

trait PageHasCluster
{
    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }
}
