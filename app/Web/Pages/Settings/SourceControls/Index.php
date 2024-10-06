<?php

namespace App\Web\Pages\Settings\SourceControls;

use App\Models\SourceControl;
use App\Web\Components\Page;
use Filament\Actions\Action;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/source-controls';

    protected static ?string $title = 'Source Controls';

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', SourceControl::class) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\SourceControlsList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect')
                ->label('Connect')
                ->icon('heroicon-o-wifi')
                ->modalHeading('Connect to a Source Control')
                ->modalSubmitActionLabel('Connect')
                ->form(Actions\Create::form())
                ->authorize('create', SourceControl::class)
                ->modalWidth(MaxWidth::Large)
                ->action(function (array $data) {
                    Actions\Create::action($data);

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
