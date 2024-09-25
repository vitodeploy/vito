<?php

namespace App\Web\Clusters\Servers\Resources\Site\Pages;

use App\Web\Clusters\Servers\Resources\Site\SiteResource;
use App\Web\Traits\PageHasServerInfoWidget;
use App\Web\Traits\PageHasCluster;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSites extends ListRecords
{
    use PageHasServerInfoWidget;
    use PageHasCluster;

    protected $listeners = ['$refresh'];

    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create a Site'),
        ];
    }
}
