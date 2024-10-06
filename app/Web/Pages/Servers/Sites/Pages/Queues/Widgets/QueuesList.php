<?php

namespace App\Web\Pages\Servers\Sites\Pages\Queues\Widgets;

use App\Actions\Queue\DeleteQueue;
use App\Actions\Queue\EditQueue;
use App\Actions\Queue\GetQueueLogs;
use App\Actions\Queue\ManageQueue;
use App\Models\Queue;
use App\Models\Site;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;

class QueuesList extends Widget
{
    public Site $site;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Queue::query()->where('site_id', $this->site->id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('command')
                ->limit(20)
                ->copyable()
                ->tooltip(fn (Queue $record) => $record->command)
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->formatStateUsing(fn (Queue $record) => $record->created_at_by_timezone)
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Queue $record) => Queue::$statusColors[$record->status])
                ->searchable()
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->actions([
                ActionGroup::make([
                    $this->editAction(),
                    $this->operationAction('start', 'heroicon-o-play'),
                    $this->operationAction('stop', 'heroicon-o-stop'),
                    $this->operationAction('restart', 'heroicon-o-arrow-path'),
                    $this->logsAction(),
                    $this->deleteAction(),
                ]),
            ]);
    }

    private function operationAction(string $type, string $icon): Action
    {
        return Action::make($type)
            ->authorize(fn (Queue $record) => auth()->user()->can('update', [$record, $this->site, $this->site->server]))
            ->label(ucfirst($type).' queue')
            ->icon($icon)
            ->action(function (Queue $record) use ($type) {
                run_action($this, function () use ($record, $type) {
                    app(ManageQueue::class)->$type($record);
                    $this->dispatch('$refresh');
                });
            });
    }

    private function logsAction(): Action
    {
        return Action::make('logs')
            ->icon('heroicon-o-eye')
            ->authorize(fn (Queue $record) => auth()->user()->can('view', [$record, $this->site, $this->site->server]))
            ->modalHeading('View Log')
            ->modalContent(function (Queue $record) {
                return view('components.console-view', [
                    'slot' => app(GetQueueLogs::class)->getLogs($record),
                    'attributes' => new ComponentAttributeBag,
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    private function editAction(): Action
    {
        return EditAction::make('edit')
            ->icon('heroicon-o-pencil-square')
            ->authorize(fn (Queue $record) => auth()->user()->can('update', [$record, $this->site, $this->site->server]))
            ->modalWidth(MaxWidth::ExtraLarge)
            ->fillForm(fn (Queue $record) => [
                'command' => $record->command,
                'user' => $record->user,
                'numprocs' => $record->numprocs,
                'auto_start' => $record->auto_start,
                'auto_restart' => $record->auto_restart,
            ])
            ->form([
                TextInput::make('command')
                    ->rules(EditQueue::rules($this->site->server)['command'])
                    ->helperText('Example: php /home/vito/your-site/artisan queue:work'),
                Select::make('user')
                    ->rules(fn (callable $get) => EditQueue::rules($this->site->server)['user'])
                    ->options([
                        'vito' => $this->site->server->ssh_user,
                        'root' => 'root',
                    ]),
                TextInput::make('numprocs')
                    ->default(1)
                    ->rules(EditQueue::rules($this->site->server)['numprocs'])
                    ->helperText('Number of processes'),
                Grid::make()
                    ->schema([
                        Checkbox::make('auto_start')
                            ->default(false),
                        Checkbox::make('auto_restart')
                            ->default(false),
                    ]),
            ])
            ->using(function (Queue $record, array $data) {
                run_action($this, function () use ($record, $data) {
                    app(EditQueue::class)->edit($record, $data);
                    $this->dispatch('$refresh');
                });
            });
    }

    private function deleteAction(): Action
    {
        return DeleteAction::make('delete')
            ->icon('heroicon-o-trash')
            ->authorize(fn (Queue $record) => auth()->user()->can('delete', [$record, $this->site, $this->site->server]))
            ->using(function (Queue $record) {
                run_action($this, function () use ($record) {
                    app(DeleteQueue::class)->delete($record);
                    $this->dispatch('$refresh');
                });
            });
    }
}
