<?php

namespace App\Web\Resources\Project;

use App\Models\Project;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProjectResource extends Resource
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $model = Project::class;

    protected static ?string $slug = 'projects';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    protected static bool $isScopedToTenant = false;

    public static function getWidgets(): array
    {
        return [
            Widgets\AddUser::class,
            Widgets\ProjectUsersList::class,
            Widgets\SelectProject::class,
            Widgets\UpdateProject::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Project $record) => Pages\ProjectSettings::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at_by_timezone')
                    ->label('Created At')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('settings')
                    ->label('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->url(fn (Project $record) => Pages\ProjectSettings::getUrl(['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'settings' => Pages\ProjectSettings::route('/{record}/settings'),
        ];
    }

    //    public static function getGlobalSearchEloquentQuery(): Builder
    //    {
    //        return parent::getGlobalSearchEloquentQuery();
    //    }
    //
    //    public static function getGlobalSearchResultUrl(Model $record): ?string
    //    {
    //        return route('filament.app.resources.projects.settings', $record);
    //    }
    //
    //    public static function getGloballySearchableAttributes(): array
    //    {
    //        return ['name'];
    //    }
    //
    //    public static function getGlobalSearchResultDetails(Model $record): array
    //    {
    //        $details = [];
    //
    //        $details['Project'] = $record->name;
    //
    //        return $details;
    //    }
}
