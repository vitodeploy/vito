<?php

namespace App\Http\Livewire\SourceControls;

use App\Actions\SourceControl\ConnectSourceControl;
use App\Models\SourceControl;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Github extends Component
{
    public string $token;

    public ?string $url;

    public function mount(): void
    {
        $this->url = request()->input('redirect') ?? null;

        $this->token = SourceControl::query()
            ->where('provider', \App\Enums\SourceControl::GITHUB)
            ->first()?->access_token ?? '';
    }

    public function connect(): void
    {
        app(ConnectSourceControl::class)->connect(\App\Enums\SourceControl::GITHUB, array_merge($this->all()));

        session()->flash('status', 'github-updated');

        if ($this->url) {
            $this->redirect($this->url);
        }
    }

    public function render(): View
    {
        return view('livewire.source-controls.github', [
            'sourceControl' => SourceControl::query()
                ->where('provider', \App\Enums\SourceControl::GITHUB)
                ->first(),
        ]);
    }
}
