<?php

namespace App\Web\Pages\Servers\Sites\Pages\SSL\Widgets;

use App\Actions\SSL\ActivateSSL;
use App\Actions\SSL\DeleteSSL;
use App\Actions\SSL\UpdateSSL;
use App\Enums\SslType;
use App\Models\Site;
use App\Models\Ssl;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
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

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    /**
     * @return Builder<Ssl>
     */
    protected function getTableQuery(): Builder
    {
        return Ssl::query()->where('site_id', $this->site->id);
    }

    protected function getTableColumns(): array
    {
        auth()->user();

        return [
            IconColumn::make('is_active')
                ->color(fn (Ssl $record): string => $record->is_active ? 'green' : 'gray')
                ->icon(fn (Ssl $record): string => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open'),
            TextColumn::make('type')
                ->searchable()
                ->sortable(),
            TextColumn::make('domains')
                ->badge()
                ->wrap(),
            TextColumn::make('created_at')
                ->formatStateUsing(fn (Ssl $record) => $record->created_at_by_timezone)
                ->sortable(),
            TextColumn::make('expires_at')
                ->formatStateUsing(fn (Ssl $record): string => $record->getDateTimeByTimezone($record->expires_at))
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
        /** @var User $user */
        $user = auth()->user();

        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                Action::make('activate-ssl')
                    ->hiddenLabel()
                    ->visible(fn (Ssl $record): bool => ! $record->is_active)
                    ->tooltip('Activate SSL')
                    ->icon('heroicon-o-lock-closed')
                    ->authorize(fn (Ssl $record) => $user->can('update', [$record->site, $this->site->server]))
                    ->requiresConfirmation()
                    ->modalHeading('Activate SSL')
                    ->modalSubmitActionLabel('Activate')
                    ->action(function (Ssl $record): void {
                        run_action($this, function () use ($record): void {
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
                    ->authorize(fn (Ssl $record) => $user->can('view', [$record, $this->site, $this->site->server]))
                    ->modalHeading('View Log')
                    ->modalContent(fn (Ssl $record) => view('components.console-view', [
                        'slot' => $record->log?->getContent(),
                        'attributes' => new ComponentAttributeBag,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('edit-ssl')
                    ->hiddenLabel()
                    ->tooltip('Edit')
                    ->icon('heroicon-o-pencil')
                    ->authorize(fn (Ssl $record) => $user->can('update', [$record, $this->site, $this->site->server]) && $record->type === SslType::CUSTOM)
                    ->modalHeading('Edit SSL')
                    ->form([
                        Section::make('Domains')
                            ->schema([
                                CheckboxList::make('domains')
                                    ->options(function (): array {
                                        $domains = [$this->site->domain];
                                        foreach ($this->site->aliases as $alias) {
                                            $domains[] = $alias;
                                        }

                                        return array_combine($domains, $domains);
                                    })
                                    ->required()
                                    ->default(function (Ssl $record): array {
                                        return array_values($record->getDomains());
                                    })
                                    ->disabled(fn (Ssl $record) => $record->type !== SslType::CUSTOM),
                            ]),
                    ])
                    ->modalSubmitActionLabel('Save')
                    ->action(function (Ssl $record, array $data): void {
                        run_action($this, function () use ($record, $data): void {
                            app(UpdateSSL::class)->update($record, $data);
                            $this->dispatch('$refresh');
                        });
                    }),
                DeleteAction::make('delete')
                    ->hiddenLabel()
                    ->tooltip('Delete')
                    ->icon('heroicon-o-trash')
                    ->authorize(fn (Ssl $record) => $user->can('delete', [$record, $this->site, $this->site->server]))
                    ->using(function (Ssl $record): void {
                        run_action($this, function () use ($record): void {
                            app(DeleteSSL::class)->delete($record);
                            $this->dispatch('$refresh');
                        });
                    }),
            ]);
    }
}
