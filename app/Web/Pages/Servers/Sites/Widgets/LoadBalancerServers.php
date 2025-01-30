<?php

namespace App\Web\Pages\Servers\Sites\Widgets;

use App\Actions\Site\UpdateLoadBalancer;
use App\Enums\LoadBalancerMethod;
use App\Models\LoadBalancerServer;
use App\Models\Site;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class LoadBalancerServers extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'components.form';

    public Site $site;

    public string $method;

    public array $servers = [];

    public function mount(): void
    {
        $this->setLoadBalancerServers();
        if (empty($this->servers)) {
            $this->servers = [
                [
                    'server' => null,
                    'port' => 80,
                    'weight' => null,
                    'backup' => false,
                ],
            ];
        }
        $this->method = $this->site->type_data['method'] ?? LoadBalancerMethod::ROUND_ROBIN;
    }

    #[On('load-balancer-updated')]
    public function setLoadBalancerServers(): void
    {
        $this->servers = $this->site->loadBalancerServers->map(function (LoadBalancerServer $server) {
            return [
                'server' => $server->ip,
                'port' => $server->port,
                'weight' => $server->weight,
                'backup' => $server->backup,
            ];
        })->toArray();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading('Load Balancer Servers')
                    ->description('You can add or remove servers from the load balancer here')
                    ->columns(3)
                    ->schema([
                        Select::make('method')
                            ->label('Balancing Method')
                            ->validationAttribute('Balancing Method')
                            ->options(
                                collect(LoadBalancerMethod::all())
                                    ->mapWithKeys(fn ($method) => [$method => $method])
                            )
                            ->rules(UpdateLoadBalancer::rules($this->site)['method']),
                        Repeater::make('servers')
                            ->schema([
                                Select::make('server')
                                    ->placeholder('Select a server')
                                    ->searchable()
                                    ->required()
                                    ->rules(UpdateLoadBalancer::rules($this->site)['servers.*.server'])
                                    ->options(function () {
                                        return $this->site->project->servers()
                                            ->where('id', '!=', $this->site->server_id)
                                            ->get()
                                            ->mapWithKeys(function ($server) {
                                                return [$server->local_ip => $server->name.' ('.$server->local_ip.')'];
                                            });
                                    }),
                                TextInput::make('port')
                                    ->default(80)
                                    ->required()
                                    ->rules(UpdateLoadBalancer::rules($this->site)['servers.*.port']),
                                TextInput::make('weight')
                                    ->rules(UpdateLoadBalancer::rules($this->site)['servers.*.weight']),
                                Toggle::make('backup')
                                    ->label('Backup')
                                    ->inline(false)
                                    ->default(false),
                            ])
                            ->columnSpan(3)
                            ->live()
                            ->reorderable(false)
                            ->columns(4)
                            ->reorderableWithDragAndDrop(false)
                            ->addActionLabel('Add Server'),
                    ])
                    ->footerActions([
                        Action::make('save')
                            ->label('Save')
                            ->action(fn () => $this->save()),
                    ]),
            ]);
    }

    public function save(): void
    {
        $this->authorize('update', [$this->site, $this->site->server]);

        $this->validate();

        run_action($this, function () {
            app(UpdateLoadBalancer::class)->update($this->site, [
                'method' => $this->method,
                'servers' => $this->servers,
            ]);

            $this->dispatch('load-balancer-updated');

            Notification::make()
                ->success()
                ->title('Load balancer updated!')
                ->send();
        });
    }
}
