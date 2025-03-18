<?php

namespace App\Web\Pages\Servers\Sites\Pages\Redirects\Actions;

use App\Actions\Redirect\CreateRedirect;
use App\Models\Server;
use App\Models\Site;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Livewire\Component;

class Create
{
    public static function form(Site $site): array
    {
        return [
            TextInput::make('from')
                ->rules(CreateRedirect::rules($site)['from']),
            TextInput::make('to')
                ->rules(CreateRedirect::rules($site)['to']),
            Select::make('mode')
                ->rules(CreateRedirect::rules($site)['mode'])
                ->options([
                    '301' => '301 - Moved Permanently',
                    '302' => '302 - Found',
                    '307' => '307 - Temporary Redirect',
                    '308' => '308 - Permanent Redirect',
                ]),
        ];
    }

    public static function action(Component $component, array $data, Site $site): void
    {
        app(CreateRedirect::class)->create($site, $data);

        $component->dispatch('$refresh');
    }
}
