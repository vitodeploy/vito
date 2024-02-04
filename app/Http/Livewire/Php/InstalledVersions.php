<?php

namespace App\Http\Livewire\Php;

use App\Actions\PHP\InstallNewPHP;
use App\Actions\PHP\InstallPHPExtension;
use App\Actions\PHP\UpdatePHPIni;
use App\Models\Server;
use App\Models\Service;
use App\SSHCommands\PHP\GetPHPIniCommand;
use App\Traits\RefreshComponentOnBroadcast;
use Exception;
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

    public ?int $extensionId = null;

    public string $extension = '';

    public function install(string $version): void
    {
        app(InstallNewPHP::class)->install($this->server, [
            'version' => $version,
        ]);

        $this->refreshComponent([]);
    }

    public function restart(int $id): void
    {
        /** @var Service $service */
        $service = Service::query()->findOrFail($id);
        $service->restart();

        $this->refreshComponent([]);
    }

    public function uninstall(): void
    {
        /** @var Service $service */
        $service = Service::query()->findOrFail($this->uninstallId);
        $service->uninstall();

        $this->refreshComponent([]);

        $this->dispatch('confirmed');
    }

    public function loadIni(int $id): void
    {
        $this->iniId = $id;
        $this->ini = 'Loading php.ini';

        /** @var Service $service */
        $service = Service::query()->findOrFail($this->iniId);

        try {
            $this->ini = $service->server->ssh()->exec(new GetPHPIniCommand($service->version));
        } catch (Throwable) {
            //
        }
    }

    public function saveIni(): void
    {
        /** @var Service $service */
        $service = Service::query()->findOrFail($this->iniId);

        app(UpdatePHPIni::class)->update($service, $this->all()['ini']);

        $this->refreshComponent([]);

        session()->flash('status', 'ini-updated');
    }

    /**
     * @throws Exception
     */
    public function installExtension(): void
    {
        /** @var Service $service */
        $service = Service::query()->findOrFail($this->extensionId);

        app(InstallPHPExtension::class)->handle($service, [
            'name' => $this->extension,
        ]);

        session()->flash('status', 'started-installation');
    }

    public function render(): View
    {
        if ($this->extensionId) {
            /** @var Service $php */
            $php = Service::query()->findOrFail($this->extensionId);
            $installedExtensions = $php->type_data['extensions'] ?? [];
        }

        return view('livewire.php.installed-versions', [
            'phps' => $this->server->services()->where('type', 'php')->get(),
            'installedExtensions' => $installedExtensions ?? [],
        ]);
    }
}
