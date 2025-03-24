<?php

namespace App\Web\Pages\Servers\Workers\Widgets;

use App\Actions\Worker\DeleteWorker;
use App\Actions\Worker\EditWorker;
use App\Actions\Worker\GetWorkerLogs;
use App\Actions\Worker\ManageWorker;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Models\Worker;
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

class WorkersList extends Widget
{
    public Server $server;

    public ?Site $site = null;

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    /**
     * @return Builder<Worker>
     */
    protected function getTableQuery(): Builder
    {
        return Worker::query()
            ->where('server_id', $this->server->id)
            ->where(function (Builder $query): void {
                if ($this->site instanceof Site) {
                    $query->where('site_id', $this->site->id);
                } else {
                    $query->whereNull('site_id');
                }
            });
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('command')
                ->limit(20)
                ->copyable()
                ->tooltip(fn (Worker $record) => $record->command)
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->formatStateUsing(fn (Worker $record) => $record->created_at_by_timezone)
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Worker $record) => Worker::$statusColors[$record->status])
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
        /** @var User $user */
        $user = auth()->user();

        return Action::make($type)
            ->authorize(fn (Worker $record) => $user->can('update', [$record, $this->server, $this->site]))
            ->label(ucfirst($type).' worker')
            ->icon($icon)
            ->action(function (Worker $record) use ($type): void {
                run_action($this, function () use ($record, $type): void {
                    app(ManageWorker::class)->$type($record);
                    $this->dispatch('$refresh');
                });
            });
    }

    private function logsAction(): Action
    {
        /** @var User $user */
        $user = auth()->user();

        return Action::make('logs')
            ->icon('heroicon-o-eye')
            ->authorize(fn (Worker $record) => $user->can('view', [$record, $this->server, $this->site]))
            ->modalHeading('View Log')
            ->modalContent(fn (Worker $record) => view('components.console-view', [
                'slot' => app(GetWorkerLogs::class)->getLogs($record),
                'attributes' => new ComponentAttributeBag,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    private function editAction(): Action
    {
        /** @var User $user */
        $user = auth()->user();

        return EditAction::make('edit')
            ->icon('heroicon-o-pencil-square')
            ->authorize(fn (Worker $record) => $user->can('update', [$record, $this->server, $this->site]))
            ->modalWidth(MaxWidth::ExtraLarge)
            ->fillForm(fn (Worker $record): array => [
                'command' => $record->command,
                'user' => $record->user,
                'numprocs' => $record->numprocs,
                'auto_start' => $record->auto_start,
                'auto_restart' => $record->auto_restart,
            ])
            ->form([
                TextInput::make('command')
                    ->rules(EditWorker::rules($this->server, $this->site)['command'])
                    ->helperText('Example: php /home/vito/your-site/artisan queue:work'),
                Select::make('user')
                    ->rules(fn (callable $get) => EditWorker::rules($this->server, $this->site)['user'])
                    ->options(array_combine($this->server->getSshUsers(), $this->server->getSshUsers())),
                TextInput::make('numprocs')
                    ->default(1)
                    ->rules(EditWorker::rules($this->server, $this->site)['numprocs'])
                    ->helperText('Number of processes'),
                Grid::make()
                    ->schema([
                        Checkbox::make('auto_start')
                            ->default(false),
                        Checkbox::make('auto_restart')
                            ->default(false),
                    ]),
            ])
            ->using(function (Worker $record, array $data): void {
                run_action($this, function () use ($record, $data): void {
                    app(EditWorker::class)->edit($record, $data);
                    $this->dispatch('$refresh');
                });
            });
    }

    private function deleteAction(): Action
    {
        /** @var User $user */
        $user = auth()->user();

        return DeleteAction::make('delete')
            ->icon('heroicon-o-trash')
            ->authorize(fn (Worker $record) => $user->can('delete', [$record, $this->server, $this->site]))
            ->using(function (Worker $record): void {
                run_action($this, function () use ($record): void {
                    app(DeleteWorker::class)->delete($record);
                    $this->dispatch('$refresh');
                });
            });
    }
}
