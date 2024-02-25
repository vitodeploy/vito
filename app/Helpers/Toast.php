<?php

namespace App\Helpers;

class Toast
{
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
        session()->flash('toast.type', $type);
        session()->flash('toast.message', $message);
    }
}
