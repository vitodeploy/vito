<?php

namespace App\Http\Livewire\SourceControls;

use App\Actions\SourceControl\ConnectSourceControl;
use App\Models\SourceControl;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Github extends Component
{
    public string $token;

    public function mount(): void
    {
        $this->token = SourceControl::query()
            ->where('provider', \App\Enums\SourceControl::GITHUB)
            ->first()?->access_token ?? '';
    }

    public function connect(): void
    {
        app(ConnectSourceControl::class)->connect(\App\Enums\SourceControl::GITHUB, $this->all());

        session()->flash('status', 'github-updated');
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
