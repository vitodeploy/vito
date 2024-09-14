<?php

namespace App\Web\Resources\Server\Pages;

use App\Web\Resources\Server\ServerResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('provider')
                ->options(collect(config('core.server_providers'))->mapWithKeys(function ($provider) {
                    return [$provider => $provider];
                })),
        ]);
    }
}
