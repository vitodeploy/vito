<?php

namespace App\Http\Livewire\Sites;

use App\Models\Site;
use App\Traits\HasToast;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class UpdateVHost extends Component
{
    use HasToast;
    use RefreshComponentOnBroadcast;

    public Site $site;

    public string $vHost = 'Loading...';

    public function loadVHost(): void
    {
        $this->vHost = $this->site->server->webserver()->handler()->getVHost($this->site);
    }

    public function update(): void
    {
        try {
            $this->site->server->webserver()->handler()->updateVHost($this->site, false, $this->vHost);

            $this->toast()->success('VHost updated successfully!');
        } catch (Throwable $e) {
            $this->toast()->error($e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.sites.update-v-host');
    }
}
