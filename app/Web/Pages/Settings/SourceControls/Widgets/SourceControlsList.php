<?php

namespace App\Web\Pages\Settings\SourceControls\Widgets;

use App\Actions\SourceControl\DeleteSourceControl;
use App\Models\SourceControl;
use App\Web\Pages\Settings\SourceControls\Actions\Edit;
use Exception;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class SourceControlsList extends Widget
{
    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return SourceControl::getByProjectId(auth()->user()->current_project_id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            IconColumn::make('provider')
                ->icon(fn (SourceControl $record) => 'icon-'.$record->provider)
                ->width(24),
            TextColumn::make('name')
                ->default(fn (SourceControl $record) => $record->profile)
                ->label('Name')
                ->searchable()
                ->sortable(),
            TextColumn::make('id')
                ->label('Global')
                ->badge()
                ->color(fn (SourceControl $record) => $record->project_id ? 'gray' : 'success')
                ->formatStateUsing(function (SourceControl $record) {
                    return $record->project_id ? 'No' : 'Yes';
                }),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (SourceControl $record) => $record->created_at_by_timezone)
                ->searchable()
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table->actions([
            EditAction::make('edit')
                ->label('Edit')
                ->modalHeading('Edit Source Control')
                ->fillForm(function (array $data, SourceControl $record) {
                    return [
                        'provider' => $record->provider,
                        'name' => $record->profile,
                        'token' => $record->provider_data['token'] ?? null,
                        'username' => $record->provider_data['username'] ?? null,
                        'password' => $record->provider_data['password'] ?? null,
                        'global' => $record->project_id === null,
                    ];
                })
                ->form(Edit::form())
                ->authorize(fn (SourceControl $record) => auth()->user()->can('update', $record))
                ->using(fn (array $data, SourceControl $record) => Edit::action($record, $data))
                ->modalWidth(MaxWidth::Medium),
            Action::make('delete')
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Source Control')
                ->authorize(fn (SourceControl $record) => auth()->user()->can('delete', $record))
                ->action(function (array $data, SourceControl $record) {
                    try {
                        app(DeleteSourceControl::class)->delete($record);

                        $this->dispatch('$refresh');
                    } catch (Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title($e->getMessage())
                            ->send();
                    }
                }),
        ]);
    }
}
