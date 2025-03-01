<?php

namespace App\Web\Pages\Servers\Sites\Widgets;

use App\Actions\Site\CreateCommand;
use App\Actions\Site\EditCommand;
use App\Actions\Site\ExecuteCommand;
use App\Models\Command;
use App\Models\CommandExecution;
use App\Models\Site;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;

class Commands extends Widget
{
    public Site $site;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Command::query()->where('site_id', $this->site->id);
    }

    protected function applySortingToTableQuery(Builder $query): Builder
    {
        return $query->latest('created_at');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name'),
            TextColumn::make('lastExecution.status')
                ->label('Status')
                ->badge()
                ->color(fn (Command $record) => CommandExecution::$statusColors[$record->lastExecution?->status])
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Last Execution At')
                ->formatStateUsing(fn (Command $record) => $record->lastExecution?->created_at_by_timezone)
                ->searchable()
                ->sortable(),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Action::make('new-command')
                ->label('Create a Command')
                ->icon('heroicon-o-plus')
                ->authorize(fn () => auth()->user()->can('create', [Command::class, $this->site, $this->site->server]))
                ->action(function (array $data) {
                    run_action($this, function () use ($data) {
                        app(CreateCommand::class)->create($this->site, $data);

                        $this->dispatch('$refresh');

                        Notification::make()
                            ->success()
                            ->title('Command created!')
                            ->send();
                    });
                })
                ->form([
                    TextInput::make('name')
                        ->rules(CreateCommand::rules()['name']),
                    TextInput::make('command')
                        ->placeholder('php artisan my:command')
                        ->rules(CreateCommand::rules()['command'])
                        ->helperText('You can use variables like ${VARIABLE_NAME} in the command. The variables will be asked when executing the command'),
                ])
                ->modalSubmitActionLabel('Create')
                ->modalHeading('New Command')
                ->modalWidth('md'),
        ];

    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->headerActions($this->getTableHeaderActions())
            ->columns($this->getTableColumns())
            ->heading('Commands')
            ->actions([
                Action::make('execute')
                    ->hiddenLabel()
                    ->tooltip('Execute')
                    ->icon('heroicon-o-play')
                    ->modalWidth(MaxWidth::Medium)
                    ->modalSubmitActionLabel('Execute')
                    ->form(function (Command $record) {
                        $form = [
                            TextInput::make('command')->default($record->command)->disabled(),
                        ];

                        foreach ($record->getVariables() as $variable) {
                            $form[] = TextInput::make('variables.'.$variable)
                                ->label($variable)
                                ->rules(fn (Get $get) => ExecuteCommand::rules($get())['variables.*']);
                        }

                        return $form;
                    })
                    ->authorize(fn (Command $record) => auth()->user()->can('update', [$record->site, $record->site->server]))
                    ->action(function (array $data, Command $record) {
                        /** @var \App\Models\User $user */
                        $user = auth()->user();
                        app(ExecuteCommand::class)->execute($record, $user, $data);
                        $this->dispatch('$refresh');
                    }),
                Action::make('logs')
                    ->hiddenLabel()
                    ->tooltip('Last Log')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('View Last Execution Log')
                    ->modalContent(function (Command $record) {
                        return view('components.console-view', [
                            'slot' => $record->lastExecution?->serverLog?->getContent() ?? 'Not executed yet',
                            'attributes' => new ComponentAttributeBag,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                EditAction::make('edit')
                    ->hiddenLabel()
                    ->tooltip('Edit')
                    ->modalHeading('Edit Command')
                    ->mutateRecordDataUsing(function (array $data, Command $record) {
                        return [
                            'name' => $record->name,
                            'command' => $record->command,
                        ];
                    })
                    ->form([
                        TextInput::make('name')
                            ->rules(EditCommand::rules()['name']),
                        TextInput::make('command')
                            ->rules(EditCommand::rules()['command'])
                            ->helperText('You can use variables like ${VARIABLE_NAME} in the command. The variables will be asked when executing the command'),

                    ])
                    ->authorize(fn (Command $record) => auth()->user()->can('update', [$record, $this->site, $this->site->server]))
                    ->using(function (array $data, Command $record) {
                        app(EditCommand::class)->edit($record, $data);
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth(MaxWidth::Medium),
                DeleteAction::make('delete')
                    ->icon('heroicon-o-trash')
                    ->hiddenLabel()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Command')
                    ->authorize(fn (Command $record) => auth()->user()->can('delete', [$record, $this->site, $this->site->server]))
                    ->using(function (array $data, Command $record) {
                        $record->delete();
                    }),
            ]);
    }
}
