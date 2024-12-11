<?php

namespace App\Web\Pages\Scripts;

use App\Actions\Script\CreateScript;
use App\Models\Script;
use App\Web\Components\Page;
use App\Web\Fields\CodeEditorField;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'scripts';

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Scripts';

    public static function getNavigationItemActiveRoutePattern(): string
    {
        return static::getRouteName().'*';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Script::class) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\ScriptsList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/scripts')
                ->openUrlInNewTab(),
            Action::make('create')
                ->label('Create a Script')
                ->icon('heroicon-o-plus')
                ->authorize('create', Script::class)
                ->modalWidth(MaxWidth::ThreeExtraLarge)
                ->form([
                    TextInput::make('name')
                        ->rules(CreateScript::rules()['name']),
                    CodeEditorField::make('content')
                        ->rules(CreateScript::rules()['content'])
                        ->helperText('You can use variables like ${VARIABLE_NAME} in the script. The variables will be asked when executing the script'),
                    Checkbox::make('global')
                        ->label('Is Global (Accessible in all projects)'),
                ])
                ->modalSubmitActionLabel('Create')
                ->action(function (array $data) {
                    run_action($this, function () use ($data) {
                        app(CreateScript::class)->create(auth()->user(), $data);

                        $this->dispatch('$refresh');
                    });
                }),
        ];
    }
}
