<?php

namespace App\Web\Resources\ServerProvider;

use App\Actions\ServerProvider\EditServerProvider;
use App\Models\ServerProvider;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServerProviderResource extends Resource
{
    protected static ?string $model = ServerProvider::class;

    protected static ?string $slug = 'server-providers';

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?int $navigationSort = 5;

    public static function getWidgets(): array
    {
        return [
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Provider')
                    ->size(24),
                TextColumn::make('name')
                    ->default(fn ($record) => $record->profile)
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('id')
                    ->label('Global')
                    ->badge()
                    ->color(fn ($record) => $record->project_id ? 'gray' : 'success')
                    ->formatStateUsing(function (ServerProvider $record) {
                        return $record->project_id ? 'No' : 'Yes';
                    }),
                TextColumn::make('created_at_by_timezone')
                    ->label('Created At')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make('edit')
                    ->label('Edit')
                    ->modalHeading('Edit Server Provider')
                    ->mutateRecordDataUsing(function (array $data, ServerProvider $record) {
                        return [
                            'name' => $record->profile,
                            'global' => $record->project_id === null,
                        ];
                    })
                    ->form(function (Form $form) {
                        return $form
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->rules(EditServerProvider::rules()['name']),
                                Checkbox::make('global')
                                    ->label('Is Global (Accessible in all projects)'),
                            ]);
                    })
                    ->using(function (array $data, ServerProvider $record) {
                        app(EditServerProvider::class)->edit($record, auth()->user(), $data);
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return ServerProvider::getByProjectId(auth()->user()->current_project_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerProviders::route('/'),
        ];
    }
}
