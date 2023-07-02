<?php

namespace App\Http\Livewire\SourceControls;

use App\Actions\SourceControl\ConnectSourceControl;
use App\Models\SourceControl;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Bitbucket extends Component
{
    public string $token;

    public function mount(): void
    {
        $this->token = SourceControl::query()
            ->where('provider', \App\Enums\SourceControl::BITBUCKET)
            ->first()?->access_token ?? '';
    }

    public function connect(): void
    {
        app(ConnectSourceControl::class)->connect(\App\Enums\SourceControl::BITBUCKET, $this->all());

        session()->flash('status', 'bitbucket-updated');
    }

    public function render(): View
    {
        return view('livewire.source-controls.bitbucket', [
            'sourceControl' => SourceControl::query()
                ->where('provider', \App\Enums\SourceControl::BITBUCKET)
                ->first(),
        ]);
    }
}
