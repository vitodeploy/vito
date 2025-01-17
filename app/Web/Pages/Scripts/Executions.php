<?php

namespace App\Web\Pages\Scripts;

use App\Actions\Script\ExecuteScript;
use App\Models\Script;
use App\Models\Server;
use App\Web\Components\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;

class Executions extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'scripts/{script}/executions';

    public Script $script;

    public function getTitle(): string|Htmlable
    {
        return $this->script->name.' - Executions';
    }

    public function mount(): void
    {
        $this->authorize('view', $this->script);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\ScriptExecutionsList::class, ['script' => $this->script]],
        ];
    }

    protected function getHeaderActions(): array
    {
        $form = [
            Select::make('server')
                ->options(function () {
                    return auth()->user()?->currentProject?->servers?->pluck('name', 'id') ?? [];
                })
                ->rules(fn (Get $get) => ExecuteScript::rules($get())['server'])
                ->searchable()
                ->reactive(),
            Select::make('user')
                ->rules(fn (Get $get) => ExecuteScript::rules($get())['user'])
                ->native(false)
                ->options(function (Get $get) {
                    $users = ['root'];

                    $server = Server::query()->find($get('server'));
                    if ($server) {
                        $users = $server->getSshUsers();
                    }

                    return array_combine($users, $users);
                }),
        ];

        foreach ($this->script->getVariables() as $variable) {
            $form[] = TextInput::make($variable)
                ->label($variable)
                ->rules(fn (Get $get) => ExecuteScript::rules($get())['variables.*']);
        }

        return [
            Action::make('execute')
                ->icon('heroicon-o-bolt')
                ->modalWidth(MaxWidth::Large)
                ->form($form)
                ->action(function (array $data) {
                    app(ExecuteScript::class)->execute($this->script, $data);

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
