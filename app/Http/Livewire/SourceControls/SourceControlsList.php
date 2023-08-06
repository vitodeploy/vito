<?php

namespace App\Http\Livewire\SourceControls;

use App\Models\SourceControl;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SourceControlsList extends Component
{
    use RefreshComponentOnBroadcast;

    public int $deleteId;

    protected $listeners = [
        '$refresh',
    ];

    public function delete(): void
    {
        $provider = SourceControl::query()->findOrFail($this->deleteId);

        $provider->delete();

        $this->refreshComponent([]);

        $this->dispatchBrowserEvent('confirmed', true);
    }

    public function render(): View
    {
        return view('livewire.source-controls.source-controls-list', [
            'sourceControls' => SourceControl::query()->latest()->get(),
        ]);
    }
}
