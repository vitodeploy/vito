<?php

namespace App\Http\Livewire\Sites;

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

    public array $inputs = [
        'type' => '',
        'web_directory' => 'public',
        'source_control' => '',
        'php_version' => '',
    ];

    /**
     * @throws SourceControlIsNotConnected
     */
    public function create(): void
    {
        $site = app(\App\Actions\Site\CreateSite::class)->create(
            $this->server,
            $this->inputs
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
