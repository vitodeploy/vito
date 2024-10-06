<?php

namespace App\Web\Pages\Servers\Sites\Widgets;

use App\Models\Site;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Widget;

class SiteSummary extends Widget implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected $listeners = ['$refresh'];

    protected static bool $isLazy = false;

    protected static string $view = 'components.infolist';

    public Site $site;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Fieldset::make('info')
                    ->label('Site Summary')
                    ->schema([
                        TextEntry::make('domain')
                            ->icon('heroicon-o-clipboard-document')
                            ->iconPosition(IconPosition::After)
                            ->copyable(),
                        TextEntry::make('path')
                            ->icon('heroicon-o-clipboard-document')
                            ->iconPosition(IconPosition::After)
                            ->copyable(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(static function ($state): string {
                                return Site::$statusColors[$state];
                            }),
                    ])
                    ->columns(3),
            ])
            ->record($this->site->refresh());
    }
}
