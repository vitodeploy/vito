<?php

namespace App\Web\Pages\Servers\Firewall;

use App\Actions\FirewallRule\CreateRule;
use App\Models\FirewallRule;
use App\Web\Pages\Servers\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/firewall';

    protected static ?string $title = 'Firewall';

    protected $listeners = ['$refresh'];

    public function mount(): void
    {
        $this->authorize('viewAny', [FirewallRule::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\RulesList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/servers/firewall')
                ->openUrlInNewTab(),
            Action::make('create')
                ->authorize(fn () => auth()->user()?->can('create', [FirewallRule::class, $this->server]))
                ->label('Create a Rule')
                ->icon('heroicon-o-plus')
                ->modalWidth(MaxWidth::Large)
                ->form([
                    Select::make('type')
                        ->native(false)
                        ->options([
                            'allow' => 'Allow',
                            'deny' => 'Deny',
                        ])
                        ->rules(CreateRule::rules()['type']),
                    Select::make('protocol')
                        ->native(false)
                        ->options([
                            'tcp' => 'TCP',
                            'udp' => 'UDP',
                        ])
                        ->rules(CreateRule::rules()['protocol']),
                    TextInput::make('port')
                        ->rules(CreateRule::rules()['port']),
                    TextInput::make('source')
                        ->rules(CreateRule::rules()['source']),
                    TextInput::make('mask')
                        ->rules(CreateRule::rules()['mask']),
                ])
                ->action(function (array $data) {
                    run_action($this, function () use ($data) {
                        app(CreateRule::class)->create($this->server, $data);

                        $this->dispatch('$refresh');

                        Notification::make()
                            ->success()
                            ->title('Firewall rule created!')
                            ->send();
                    });
                }),
        ];
    }
}
