<?php

namespace App\Http\Livewire\Sites;

use App\Enums\SiteType;
use App\Exceptions\SourceControlIsNotConnected;
use App\Models\Server;
use App\Models\SourceControl;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateSite extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public string $type = SiteType::LARAVEL;

    public string $domain;

    public string $alias;

    public string $php_version = '';

    public string $web_directory = 'public';

    public string $source_control = '';

    public string $repository;

    public string $branch;

    public bool $composer;

    /**
     * @throws SourceControlIsNotConnected
     */
    public function create(): void
    {
        $site = app(\App\Actions\Site\CreateSite::class)->create(
            $this->server,
            $this->all()
        );

        $this->redirect(route('servers.sites.show', [
            'server' => $site->server,
            'site' => $site,
        ]));
    }

    public function render(): View
    {
        return view('livewire.sites.create-site', [
            'sourceControls' => SourceControl::all(),
        ]);
    }
}
