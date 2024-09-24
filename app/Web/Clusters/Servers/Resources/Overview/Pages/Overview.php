<?php

namespace App\Web\Clusters\Servers\Resources\Overview\Pages;

use App\Web\Clusters\Servers\Resources\Overview\OverviewResource;
use App\Web\Traits\HasServerInfoWidget;
use App\Web\Traits\PageHasCluster;
use App\Web\Traits\PageHasWidgets;
use Filament\Resources\Pages\Page;

class Overview extends Page
{
    use HasServerInfoWidget;
    use PageHasCluster;
    use PageHasWidgets;

    protected static string $resource = OverviewResource::class;

    public function getWidgets(): array
    {
        return [
        ];
    }
}
