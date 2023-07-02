<?php

namespace App\Traits;

trait RefreshComponentOnBroadcast
{
    public function getListeners(): array
    {
        return [
            'echo-private:app,Broadcast' => 'refreshComponent',
            'refreshComponent' => '$refresh',
            '$refresh',
        ];
    }

    public function refreshComponent(array $data): void
    {
        $this->emit('refreshComponent');
    }
}
