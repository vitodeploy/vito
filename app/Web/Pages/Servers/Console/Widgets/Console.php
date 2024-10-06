<?php

namespace App\Web\Pages\Servers\Console\Widgets;

use App\Models\Server;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;

class Console extends Component implements HasForms
{
    use InteractsWithForms;

    public Server $server;

    protected $listeners = ['$refresh'];

    public bool $running = false;

    public string $output = '';

    public ?array $data = [];

    public function mount(): void
    {
        $this->data['user'] = $this->server->ssh_user;
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Grid::make()
                    ->columns(6)
                    ->schema([
                        Select::make('user')
                            ->options([
                                'root' => 'root',
                                'vito' => 'vito',
                            ])
                            ->maxWidth('sm')
                            ->hiddenLabel(),
                        TextInput::make('command')
                            ->hiddenLabel()
                            ->placeholder('Command')
                            ->columnSpan(5)
                            ->suffixActions(
                                [
                                    Action::make('run')
                                        ->label('Run')
                                        ->icon('heroicon-o-play')
                                        ->color('primary')
                                        ->visible(! $this->running)
                                        ->action(function () {
                                            $this->running = true;
                                            $ssh = $this->server->ssh($this->data['user']);
                                            $log = 'console-'.time();
                                            $ssh->exec(command: $this->data['command'], log: $log, stream: true, streamCallback: function ($output) {
                                                $this->output .= $output;
                                                $this->stream(
                                                    to: 'output',
                                                    content: $output,
                                                );
                                            });
                                        }),
                                    Action::make('stop')
                                        ->view('web.components.dynamic-widget', [
                                            'widget' => StopCommand::class,
                                            'params' => [],
                                        ]),
                                ],
                            ),
                    ]),
            ]);
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>
            <x-console-view wire:stream="output">
                {{ $this->output }}
            </x-console-view>
            <div class="mt-5">
                {{ $this->form }}
            </div>
        </div>
        BLADE;
    }
}
