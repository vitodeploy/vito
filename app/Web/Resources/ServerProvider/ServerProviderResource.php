<?php

namespace App\Web\Resources\ServerProvider;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Actions\ServerProvider\DeleteServerProvider;
use App\Actions\ServerProvider\EditServerProvider;
use App\Models\ServerProvider;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServerProviderResource extends Resource
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $model = ServerProvider::class;

    protected static ?string $slug = 'server-providers';

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?int $navigationSort = 5;

    public static function formSchema(): array
    {
        return [
            Select::make('provider')
                ->options(
                    collect(config('core.server_providers'))
                        ->filter(fn ($provider) => $provider != \App\Enums\ServerProvider::CUSTOM)
                        ->mapWithKeys(fn ($provider) => [$provider => $provider])
                )
                ->live()
                ->reactive()
                ->rules(CreateServerProvider::rules()['provider']),
            TextInput::make('name')
                ->rules(CreateServerProvider::rules()['name']),
            TextInput::make('token')
                ->label('API Key')
                ->validationAttribute('API Key')
                ->visible(fn ($get) => in_array($get('provider'), [
                    \App\Enums\ServerProvider::DIGITALOCEAN,
                    \App\Enums\ServerProvider::LINODE,
                    \App\Enums\ServerProvider::VULTR,
                    \App\Enums\ServerProvider::HETZNER,
                ]))
                ->rules(fn ($get) => CreateServerProvider::providerRules($get())['token']),
            TextInput::make('key')
                ->label('Access Key')
                ->visible(function ($get) {
                    return $get('provider') == \App\Enums\ServerProvider::AWS;
                })
                ->rules(fn ($get) => CreateServerProvider::providerRules($get())['key']),
            TextInput::make('secret')
                ->label('Secret')
                ->visible(fn ($get) => $get('provider') == \App\Enums\ServerProvider::AWS)
                ->rules(fn ($get) => CreateServerProvider::providerRules($get())['secret']),
            Checkbox::make('global')
                ->label('Is Global (Accessible in all projects)'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::formSchema())
            ->columns(1);
    }

    /**
     * @throws Exception
     */
    public static function createAction(array $data): void
    {
        try {
            app(CreateServerProvider::class)->create(auth()->user(), $data);
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

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
                    })
                    ->modalWidth(MaxWidth::Medium),
                DeleteAction::make('delete')
                    ->label('Delete')
                    ->modalHeading('Delete Server Provider')
                    ->using(function (array $data, ServerProvider $record) {
                        app(DeleteServerProvider::class)->delete($record);
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
