<?php

namespace App\Web\Pages\Servers\Sites\Pages\SSL\Widgets;

use App\Actions\SSL\DeleteSSL;
use App\Models\Site;
use App\Models\Ssl;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;

class SslsList extends Widget
{
    public Site $site;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Ssl::query()->where('site_id', $this->site->id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('type')
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->formatStateUsing(fn (Ssl $record) => $record->created_at_by_timezone)
                ->sortable(),
            TextColumn::make('expires_at')
                ->formatStateUsing(fn (Ssl $record) => $record->getDateTimeByTimezone($record->expires_at))
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Ssl $record) => Ssl::$statusColors[$record->status])
                ->searchable()
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->actions([
                Action::make('logs')
                    ->hiddenLabel()
                    ->tooltip('Logs')
                    ->icon('heroicon-o-eye')
                    ->authorize(fn (Ssl $record) => auth()->user()->can('view', [$record, $this->site, $this->site->server]))
                    ->modalHeading('View Log')
                    ->modalContent(function (Ssl $record) {
                        return view('components.console-view', [
                            'slot' => $record->log?->getContent(),
                            'attributes' => new ComponentAttributeBag,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                DeleteAction::make('delete')
                    ->hiddenLabel()
                    ->tooltip('Delete')
                    ->icon('heroicon-o-trash')
                    ->authorize(fn (Ssl $record) => auth()->user()->can('delete', [$record, $this->site, $this->site->server]))
                    ->using(function (Ssl $record) {
                        run_action($this, function () use ($record) {
                            app(DeleteSSL::class)->delete($record);
                            $this->dispatch('$refresh');
                        });
                    }),
            ]);
    }
}
