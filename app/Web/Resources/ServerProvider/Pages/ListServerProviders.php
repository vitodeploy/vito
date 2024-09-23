<?php

namespace App\Web\Resources\ServerProvider\Pages;

use App\Web\Resources\ServerProvider\ServerProviderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListServerProviders extends ListRecords
{
    protected static string $resource = ServerProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Connect')
                ->modalHeading('Connect to a Server Provider')
                ->modalSubmitActionLabel('Connect')
                ->createAnother(false)
                ->modalWidth(MaxWidth::Medium)
                ->using(fn (array $data) => static::$resource::createAction($data)),
        ];
    }
}
