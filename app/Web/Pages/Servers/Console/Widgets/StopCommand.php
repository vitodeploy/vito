<?php

namespace App\Web\Pages\Servers\Console\Widgets;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class StopCommand extends Component implements HasForms
{
    use InteractsWithForms;

    public function stop(): void
    {
        Cache::put('console', 0);
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>
            <x-filament::icon
                icon="heroicon-o-stop"
                wire:click="stop"
                class="h-5 w-5 text-danger-400 cursor-pointer"
            />
        </div>
        BLADE;
    }
}
