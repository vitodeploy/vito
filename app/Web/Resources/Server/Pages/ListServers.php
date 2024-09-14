<?php

namespace App\Web\Resources\Server\Pages;

use App\Web\Resources\Server\ServerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServers extends ListRecords
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create a Server'),
        ];
    }
}
