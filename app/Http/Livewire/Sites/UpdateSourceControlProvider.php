<?php

namespace App\Http\Livewire\Sites;

use App\Actions\Site\UpdateSourceControl;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UpdateSourceControlProvider extends Component
{
    public Site $site;

    public ?int $source_control = null;

    public function update(): void
    {
        app(UpdateSourceControl::class)->update($this->site, $this->all());

        session()->flash('status', 'source-control-updated');
    }

    public function render(): View
    {
        if (! $this->source_control) {
            $this->source_control = $this->site->source_control_id;
        }

        return view('livewire.sites.update-source-control-provider');
    }
}
