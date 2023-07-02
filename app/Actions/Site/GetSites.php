<?php

namespace App\Actions\Site;

use App\Models\Server;
use Illuminate\Database\Eloquent\Collection;

class GetSites
{
    public function handle(Server $server): Collection
    {
        return $server->sites()->orderBy('id', 'desc')->get();
    }
}
