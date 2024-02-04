<?php

namespace App\Http\Livewire\Services;

use App\Actions\Service\InstallPHPMyAdmin as InstallPHPMyAdminAction;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InstallPHPMyAdmin extends Component
{
    public Server $server;

    public string $allowed_ip;

    public string $port = '5433';

    public function install(): void
    {
        app(InstallPHPMyAdminAction::class)->install($this->server, $this->all());

        $this->dispatch('started');

        $this->dispatch('$refresh')->to(ServicesList::class);
    }

    public function render(): View
    {
        return view('livewire.services.install-phpmyadmin');
    }
}
