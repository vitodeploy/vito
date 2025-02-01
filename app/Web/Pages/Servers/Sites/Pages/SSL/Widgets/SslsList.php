<?php

namespace App\Web\Pages\Servers\Sites\Pages\SSL\Widgets;

use App\Actions\SSL\ActivateSSL;
use App\Actions\SSL\DeleteSSL;
use App\Models\Site;
use App\Models\Ssl;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
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

    protected function getTableColumns(): array
    {
        return [
            IconColumn::make('is_active')
                ->color(fn (Ssl $record) => $record->is_active ? 'green' : 'gray')
                ->icon(fn (Ssl $record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open'),
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

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                Action::make('activate-ssl')
                    ->hiddenLabel()
                    ->visible(fn (Ssl $record) => ! $record->is_active)
                    ->tooltip('Activate SSL')
                    ->icon('heroicon-o-lock-closed')
                    ->authorize(fn (Ssl $record) => auth()->user()->can('update', [$record->site, $this->site->server]))
                    ->requiresConfirmation()
                    ->modalHeading('Activate SSL')
                    ->modalSubmitActionLabel('Activate')
                    ->action(function (Ssl $record) {
                        run_action($this, function () use ($record) {
                            app(ActivateSSL::class)->activate($record);

                            Notification::make()
                                ->success()
                                ->title('SSL has been activated.')
                                ->send();
                        });
                    }),
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
