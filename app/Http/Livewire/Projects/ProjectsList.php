<?php

namespace App\Http\Livewire\Projects;

use App\Actions\Projects\DeleteProject;
use App\Traits\HasToast;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ProjectsList extends Component
{
    use HasToast;
    use RefreshComponentOnBroadcast;

    protected $listeners = [
        '$refresh',
    ];

    public int $deleteId;

    public function delete(): void
    {
        try {
            app(DeleteProject::class)->delete(auth()->user(), $this->deleteId);

            $this->redirect(route('projects'));

            return;
        } catch (ValidationException $e) {
            $this->toast()->error($e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.projects.projects-list', [
            'projects' => auth()->user()->projects()->orderByDesc('id')->get(),
        ]);
    }
}
