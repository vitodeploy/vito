<?php

namespace App\Http\Livewire\Projects;

use App\Traits\HasToast;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateProject extends Component
{
    use HasToast;
    use RefreshComponentOnBroadcast;

    public bool $open = false;

    public array $inputs = [];

    public function create(): void
    {
        app(\App\Actions\Projects\CreateProject::class)
            ->create(auth()->user(), $this->inputs);

        $this->emitTo(ProjectsList::class, '$refresh');

        $this->dispatchBrowserEvent('created', true);
    }

    public function render(): View
    {
        if (request()->query('create')) {
            $this->open = true;
        }

        return view('livewire.projects.create-project');
    }
}
