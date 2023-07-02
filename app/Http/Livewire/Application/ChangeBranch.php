<?php

namespace App\Http\Livewire\Application;

use App\Actions\Site\UpdateBranch;
use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ChangeBranch extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public string $branch;

    public function mount(): void
    {
        $this->branch = $this->site->branch;
    }

    public function change(): void
    {
        app(UpdateBranch::class)->update($this->site, $this->all());

        session()->flash('status', 'updating-branch');
    }

    public function render(): View
    {
        return view('livewire.application.change-branch');
    }
}
