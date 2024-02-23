<?php

namespace App\Http\Livewire\Ssl;

use App\Models\Site;
use App\Traits\HasToast;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SslsList extends Component
{
    use HasToast;
    use RefreshComponentOnBroadcast;

    public Site $site;

    public int $deleteId;

    public function delete(): void
    {
        $ssl = $this->site->ssls()->where('id', $this->deleteId)->firstOrFail();

        $ssl->remove();

        $this->refreshComponent([]);

        $this->dispatch('confirmed');
    }

    public function refreshComponent(array $data): void
    {
        if (isset($data['type']) && $data['type'] == 'deploy-ssl-failed') {
            $this->toast()->error(__('SSL creation failed!'));
        }

        $this->dispatch('refreshComponent');
    }

    public function render(): View
    {
        return view('livewire.ssl.ssls-list', [
            'ssls' => $this->site->ssls,
        ]);
    }
}
