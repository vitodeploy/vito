<?php

namespace App\Web\Resources\Server\Widgets;

use App\Actions\Server\EditServer;
use App\Models\Server;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class UpdateServerInfo extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static bool $isLazy = false;

    protected static string $view = 'web.components.form';

    public Server $server;

    public string $name;

    public string $ip;

    public string $port;

    public function mount(Server $server): void
    {
        $this->server = $server;
        $this->name = $server->name;
        $this->ip = $server->ip;
        $this->port = $server->port;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading('Update Server')
                    ->description('You can edit your server\'s information here')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->rules(EditServer::rules($this->server)['name']),
                        TextInput::make('ip')
                            ->label('IP Address')
                            ->rules(EditServer::rules($this->server)['ip']),
                        TextInput::make('port')
                            ->label('Port')
                            ->rules(EditServer::rules($this->server)['port']),
                    ])
                    ->footerActions([
                        Actions\Action::make('submit')
                            ->label('Save')
                            ->action(fn () => $this->submit()),
                    ]),
            ]);
    }

    public function submit(): void
    {
        $this->authorize('update', $this->server);

        $this->validate();

        app(EditServer::class)->edit($this->server, $this->all());

        $this->dispatch('$refresh');

        Notification::make()
            ->success()
            ->title('Server updated!')
            ->send();
    }
}
