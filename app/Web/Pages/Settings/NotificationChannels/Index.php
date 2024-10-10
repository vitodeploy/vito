<?php

namespace App\Web\Pages\Settings\NotificationChannels;

use App\Models\NotificationChannel;
use App\Web\Components\Page;
use Filament\Actions\Action;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/notification-channels';

    protected static ?string $title = 'Notification Channels';

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?int $navigationSort = 8;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', NotificationChannel::class) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\NotificationChannelsList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add')
                ->label('Add new Channel')
                ->icon('heroicon-o-plus')
                ->modalHeading('Add a new Channel')
                ->modalSubmitActionLabel('Add')
                ->form(Actions\Create::form())
                ->authorize('create', NotificationChannel::class)
                ->modalWidth(MaxWidth::Large)
                ->action(function (array $data) {
                    try {
                        Actions\Create::action($data);

                        $this->dispatch('$refresh');
                    } catch (\Exception) {
                        $this->halt();
                    }
                }),
        ];
    }
}
