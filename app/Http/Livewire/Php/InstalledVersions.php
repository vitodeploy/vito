<?php

namespace App\Http\Livewire\Php;

use App\Actions\PHP\InstallNewPHP;
use App\Actions\PHP\UpdatePHPIni;
use App\Models\Server;
use App\Models\Service;
use App\SSHCommands\PHP\GetPHPIniCommand;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class InstalledVersions extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public int $uninstallId;

    public int $iniId;

    public string $ini = 'Loading php.ini';

    public function install(string $version): void
    {
        app(InstallNewPHP::class)->install($this->server, [
            'version' => $version,
        ]);

        $this->refreshComponent([]);
    }

    public function restart(int $id): void
    {
        $service = Service::query()->findOrFail($id);
        $service->restart();

        $this->refreshComponent([]);
    }

    public function uninstall(): void
    {
        $service = Service::query()->findOrFail($this->uninstallId);
        $service->uninstall();

        $this->refreshComponent([]);

        $this->dispatchBrowserEvent('confirmed', true);
    }

    public function loadIni(int $id): void
    {
        $this->iniId = $id;
        $this->ini = 'Loading php.ini';

        $service = Service::query()->findOrFail($this->iniId);

        try {
            $this->ini = $service->server->ssh()->exec(new GetPHPIniCommand($service->version));
        } catch (Throwable) {
            //
        }
    }

    public function saveIni(): void
    {
        $service = Service::query()->findOrFail($this->iniId);

        app(UpdatePHPIni::class)->update($service, $this->all()['ini']);

        $this->refreshComponent([]);

        session()->flash('status', 'ini-updated');
    }

    public function render(): View
    {
        return view('livewire.php.installed-versions', [
            'phps' => $this->server->services()->where('type', 'php')->get(),
        ]);
    }
}
