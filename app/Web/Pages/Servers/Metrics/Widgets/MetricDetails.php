<?php

namespace App\Web\Pages\Servers\Metrics\Widgets;

use App\Models\Metric;
use App\Models\Server;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class MetricDetails extends Widget implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected $listeners = ['$refresh'];

    protected static bool $isLazy = false;

    protected static string $view = 'components.infolist';

    public Server $server;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->server->metrics()->latest()->first() ?? new Metric)
            ->schema([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->heading('Memory')
                            ->description('More details on memory')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('memory_total')
                                    ->label('Total Memory')
                                    ->alignRight()
                                    ->formatStateUsing(fn (Metric $record) => Number::fileSize($record->memory_total_in_bytes, 2))
                                    ->inlineLabel(),
                                TextEntry::make('memory_used')
                                    ->label('Used Memory')
                                    ->alignRight()
                                    ->formatStateUsing(fn (Metric $record) => Number::fileSize($record->memory_used_in_bytes, 2))
                                    ->inlineLabel(),
                                TextEntry::make('memory_free')
                                    ->label('Free Memory')
                                    ->formatStateUsing(fn (Metric $record) => Number::fileSize($record->memory_free_in_bytes, 2))
                                    ->alignRight()
                                    ->inlineLabel(),
                            ]),
                        Section::make()
                            ->heading('Disk')
                            ->description('More details on disk')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('disk_total')
                                    ->label('Total Disk')
                                    ->formatStateUsing(fn (Metric $record) => Number::fileSize($record->disk_total_in_bytes, 2))
                                    ->alignRight()
                                    ->inlineLabel(),
                                TextEntry::make('disk_used')
                                    ->label('Used Disk')
                                    ->formatStateUsing(fn (Metric $record) => Number::fileSize($record->disk_used_in_bytes, 2))
                                    ->alignRight()
                                    ->inlineLabel(),
                                TextEntry::make('disk_free')
                                    ->label('Free Disk')
                                    ->formatStateUsing(fn (Metric $record) => Number::fileSize($record->disk_free_in_bytes, 2))
                                    ->alignRight()
                                    ->inlineLabel(),
                            ]),
                    ]),
            ]);
    }
}
