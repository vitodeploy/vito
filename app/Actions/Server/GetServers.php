<?php

namespace App\Actions\Server;

use App\Models\Server;
use Illuminate\Database\Eloquent\Collection;

class GetServers
{
    public function handle(): Collection
    {
        return Server::query()->latest()->get();
    }
}
