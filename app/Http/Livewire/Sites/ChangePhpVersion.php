<?php

namespace App\Http\Livewire\Sites;

use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ChangePhpVersion extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public string $version;

    public function mount(Site $site): void
    {
        $this->version = $site->php_version;
    }

    public function change(): void
    {
        $this->site->changePHPVersion($this->version);

        session()->flash('status', 'changing-php-version');
    }

    public function refreshComponent(array $data): void
    {
        if (isset($data['type'])) {
            session()->flash('status', $data['type']);
        }
    }

    public function render(): View
    {
        return view('livewire.sites.change-php-version');
    }
}
