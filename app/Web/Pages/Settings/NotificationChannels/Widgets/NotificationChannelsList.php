<?php

namespace App\Web\Pages\Settings\NotificationChannels\Widgets;

use App\Models\NotificationChannel;
use App\Models\User;
use App\Web\Pages\Settings\NotificationChannels\Actions\Edit;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class NotificationChannelsList extends Widget
{
    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    /**
     * @return Builder<NotificationChannel>
     */
    protected function getTableQuery(): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        return NotificationChannel::getByProjectId($user->current_project_id);
    }

    protected function getTableColumns(): array
    {
        return [
            IconColumn::make('provider')
                ->icon(fn (NotificationChannel $record): string => 'icon-'.$record->provider)
                ->width(24),
            TextColumn::make('label')
                ->default(fn (NotificationChannel $record) => $record->label)
                ->searchable()
                ->sortable(),
            TextColumn::make('id')
                ->label('Global')
                ->badge()
                ->color(fn (NotificationChannel $record): string => $record->project_id ? 'gray' : 'success')
                ->formatStateUsing(fn (NotificationChannel $record): string => $record->project_id ? 'No' : 'Yes'),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (NotificationChannel $record) => $record->created_at_by_timezone)
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
                EditAction::make('edit')
                    ->modalHeading('Edit Notification Channel')
                    ->mutateRecordDataUsing(fn (array $data, NotificationChannel $record): array => [
                        'label' => $record->label,
                        'global' => ! $record->project_id,
                    ])
                    ->form(Edit::form())
                    ->authorize(fn (NotificationChannel $record) => $user->can('update', $record))
                    ->using(fn (array $data, NotificationChannel $record) => Edit::action($record, $data))
                    ->modalWidth(MaxWidth::Medium),
                DeleteAction::make('delete')
                    ->modalHeading('Delete Notification Channel')
                    ->authorize(fn (NotificationChannel $record) => $user->can('delete', $record))
                    ->using(function (array $data, NotificationChannel $record): void {
                        $record->delete();
                    }),
            ]);
    }
}
