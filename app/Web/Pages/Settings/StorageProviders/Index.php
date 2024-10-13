<?php

namespace App\Web\Pages\Settings\StorageProviders;

use App\Enums\StorageProvider;
use App\Web\Components\Page;
use Filament\Actions\Action;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/storage-providers';

    protected static ?string $title = 'Storage Providers';

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', StorageProvider::class) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\StorageProvidersList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect')
                ->label('Connect')
                ->icon('heroicon-o-wifi')
                ->modalHeading('Connect to a Storage Provider')
                ->modalSubmitActionLabel('Connect')
                ->form(Actions\Create::form())
                ->authorize('create', StorageProvider::class)
                ->modalWidth(MaxWidth::ExtraLarge)
                ->action(function (array $data) {
                    Actions\Create::action($data);

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
