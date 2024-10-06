<?php

namespace App\Web\Pages\Settings\Tags;

use App\Models\Tag;
use App\Web\Components\Page;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/tags';

    protected static ?string $title = 'Tags';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Tag::class) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\TagsList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create a Tag')
                ->modalSubmitActionLabel('Save')
                ->createAnother(false)
                ->form(Actions\Create::form())
                ->authorize('create', Tag::class)
                ->modalWidth(MaxWidth::ExtraLarge)
                ->using(function (array $data) {
                    Actions\Create::action($data);

                    $this->dispatch('$refresh');

                    Notification::make()
                        ->success()
                        ->title('Tag created!')
                        ->send();
                }),
        ];
    }
}
