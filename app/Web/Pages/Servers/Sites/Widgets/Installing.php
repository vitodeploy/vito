<?php

namespace App\Web\Pages\Servers\Sites\Widgets;

use App\Models\Site;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Widgets\Widget;
use Illuminate\View\ComponentAttributeBag;

class Installing extends Widget implements HasForms, HasInfolists
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
                Section::make()
                    ->heading('Installing Site')
                    ->icon(function () {
                        if ($this->site->isInstallationFailed()) {
                            return 'heroicon-o-x-circle';
                        }

                        return view('filament::components.loading-indicator')
                            ->with('attributes', new ComponentAttributeBag([
                                'class' => 'mr-2 size-[24px] text-primary-400',
                            ]));
                    })
                    ->iconColor($this->site->isInstallationFailed() ? 'danger' : 'primary')
                    ->schema([
                        ViewEntry::make('progress')
                            ->hiddenLabel()
                            ->view('components.progress-bar')
                            ->viewData([
                                'value' => $this->site->progress,
                            ]),
                    ]),
            ])
            ->record($this->site->refresh());
    }
}
