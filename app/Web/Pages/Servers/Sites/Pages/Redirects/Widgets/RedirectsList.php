<?php

namespace App\Web\Pages\Servers\Sites\Pages\Redirects\Widgets;

use App\Actions\Redirect\DeleteRedirect;
use App\Models\Site;
use App\Models\Redirect;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;

class RedirectsList extends Widget
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
        return Redirect::query()->where('site_id', $this->site->id);
    }

    protected function getTableColumns(): array
    {
        auth()->user();

        return [
            TextColumn::make('from')
                ->searchable()
                ->sortable(),
            TextColumn::make('to')
                ->searchable()
                ->sortable(),
            TextColumn::make('mode')
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->formatStateUsing(fn (Redirect $record) => $record->created_at)
                ->sortable()
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
                DeleteAction::make('delete')
                    ->hiddenLabel()
                    ->tooltip('Delete')
                    ->icon('heroicon-o-trash')
                    ->authorize(fn (Redirect $record) => $user->can('delete', [$this->site, $this->site->server]))
                    ->using(function (Redirect $record): void {
                        run_action($this, function () use ($record): void {
                            app(DeleteRedirect::class)->delete($this->site, $record);
                            $this->dispatch('$refresh');
                        });
                    }),
            ]);
    }
}
