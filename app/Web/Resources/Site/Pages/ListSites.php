<?php

namespace App\Web\Resources\Site\Pages;

use App\Web\Resources\Site\SiteResource;
use App\Web\Traits\HasServerInfoWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSites extends ListRecords
{
    use HasServerInfoWidget;

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
