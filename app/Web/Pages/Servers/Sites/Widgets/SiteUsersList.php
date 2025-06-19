<?php

namespace App\Web\Pages\Servers\Sites\Widgets;

use App\Models\Site;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class SiteUsersList extends Widget
{
    /**
     * @var array<string, string>
     */
    protected $listeners = ['userAdded' => '$refresh'];

    public Site $site;

    public function mount(Site $site): void
    {
        $this->site = $site;
    }

    /**
     * @return Builder<User>
     */
    protected function getTableQuery(): Builder
    {
        return User::query()->whereHas('sites', function (Builder $query): void {
            $query->where('site_id', $this->site->id);
        });
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')->width('20%'),
            Tables\Columns\TextColumn::make('name')->width('20%'),
            Tables\Columns\TextColumn::make('email')->width('20%'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove user from site')
                    ->visible(fn ($record): bool => $this->authorize('update', [$this->site, $this->site->server])->allowed() && $record->id !== auth()->id())
                    ->using(function ($record): void {
                        $this->site->users()->detach($record);
                    }),
            ])
            ->paginated(false);
    }
}