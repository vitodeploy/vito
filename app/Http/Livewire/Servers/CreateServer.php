<?php

namespace App\Http\Livewire\Servers;

use App\Enums\Database;
use App\Enums\OperatingSystem;
use App\Enums\ServerType;
use App\Enums\Webserver;
use App\Models\ServerProvider;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class CreateServer extends Component
{
    public string $provider = 'custom';

    public string $server_provider = '';

    public string $type = ServerType::REGULAR;

    public string $name;

    public string $ip;

    public string $port;

    public string $os = OperatingSystem::UBUNTU22;

    public string $webserver = Webserver::NGINX;

    public string $database = Database::MYSQL80;

    public string $php = '8.2';

    public string $plan = '';

    public string $region = '';

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        $server = app(\App\Actions\Server\CreateServer::class)->create(
            auth()->user(),
            $this->all()
        );

        $this->redirect(route('servers.show', ['server' => $server]));
    }

    public function render(): View
    {
        $serverProviders = ServerProvider::query()->where('provider', $this->provider)->get();

        return view(
            'livewire.servers.create-server',
            compact([
                'serverProviders',
            ])
        );
    }
}
