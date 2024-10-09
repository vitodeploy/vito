<?php

namespace App\Web\Pages\Servers\Logs\Widgets;

use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Site;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;

class LogsList extends Widget
{
    public Server $server;

    public ?Site $site = null;

    public ?string $label = '';

    public bool $remote = false;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return ServerLog::query()
            ->where('server_id', $this->server->id)
            ->where(function (Builder $query) {
                if ($this->site) {
                    $query->where('site_id', $this->site->id);
                }
            })
            ->where('is_remote', $this->remote);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Event')
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
                ->sortable(),
        ];
    }

    protected function applyDefaultSortingToTableQuery(Builder $query): Builder
    {
        return $query->latest('created_at');
    }

    /**
     * @throws Exception
     */
    public function getTable(): Table
    {
        return $this->table
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->heading($this->label)
            ->actions([
                Action::make('view')
                    ->hiddenLabel()
                    ->tooltip('View')
                    ->icon('heroicon-o-eye')
                    ->authorize(fn ($record) => auth()->user()->can('view', $record))
                    ->modalHeading('View Log')
                    ->modalContent(function (ServerLog $record) {
                        return view('components.console-view', [
                            'slot' => $record->getContent(),
                            'attributes' => new ComponentAttributeBag,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('download')
                    ->hiddenLabel()
                    ->tooltip('Download')
                    ->color('gray')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->authorize(fn ($record) => auth()->user()->can('view', $record))
                    ->action(fn (ServerLog $record) => $record->download()),
                DeleteAction::make()
                    ->hiddenLabel()
                    ->tooltip('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->authorize(fn ($record) => auth()->user()->can('delete', $record)),
            ])
            ->bulkActions(
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->authorize(auth()->user()->can('deleteMany', [ServerLog::class, $this->server])),
                ])
            );
    }
}
