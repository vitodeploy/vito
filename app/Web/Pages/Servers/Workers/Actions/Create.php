<?php

namespace App\Web\Pages\Servers\Workers\Actions;

use App\Actions\Worker\CreateWorker;
use App\Models\Server;
use App\Models\Site;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Livewire\Component;

class Create
{
    /**
     * @return array<int, mixed>
     */
    public static function form(Server $server, ?Site $site = null): array
    {
        return [
            TextInput::make('command')
                ->rules(CreateWorker::rules($server, $site)['command'])
                ->helperText('Example: php /home/vito/your-site/artisan queue:work'),
            Select::make('user')
                ->rules(fn (callable $get) => CreateWorker::rules($server, $site)['user'])
                ->options(
                    $site instanceof Site ?
                        array_combine($site->getSshUsers(), $site->getSshUsers()) :
                        array_combine($server->getSshUsers(), $server->getSshUsers())
                ),
            TextInput::make('numprocs')
                ->default(1)
                ->rules(CreateWorker::rules($server, $site)['numprocs'])
                ->helperText('Number of processes'),
            Grid::make()
                ->schema([
                    Checkbox::make('auto_start')
                        ->default(false),
                    Checkbox::make('auto_restart')
                        ->default(false),
                ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function action(Component $component, array $data, Server $server, ?Site $site = null): void
    {
        app(CreateWorker::class)->create(
            $server,
            $data,
            $site
        );

        $component->dispatch('$refresh');
    }
}
