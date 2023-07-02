<?php

namespace App\Helpers;

use Livewire\Component;

class Toast
{
    public function __construct(public Component $component)
    {
    }

    public function success(string $message): void
    {
        $this->toast('success', $message);
    }

    public function error(string $message): void
    {
        $this->toast('error', $message);
    }

    public function warning(string $message): void
    {
        $this->toast('warning', $message);
    }

    public function info(string $message): void
    {
        $this->toast('info', $message);
    }

    private function toast(string $type, string $message): void
    {
        $this->component->dispatchBrowserEvent('toast', [
            'type' => $type,
            'message' => $message,
        ]);
    }
}
