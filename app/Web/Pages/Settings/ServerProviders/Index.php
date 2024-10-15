<?php

namespace App\Web\Pages\Settings\ServerProviders;

use App\Enums\ServerProvider;
use App\Web\Components\Page;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/server-providers';

    protected static ?string $title = 'Server Providers';

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', ServerProvider::class) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\ServerProvidersList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create')
                ->label('Connect')
                ->icon('heroicon-o-wifi')
                ->modalHeading('Connect to a Server Provider')
                ->modalSubmitActionLabel('Connect')
                ->createAnother(false)
                ->form(Actions\Create::form())
                ->authorize('create', ServerProvider::class)
                ->modalWidth(MaxWidth::Medium)
                ->using(function (array $data) {
                    Actions\Create::action($data);

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
