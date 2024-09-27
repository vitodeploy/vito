<?php

namespace App\Traits;

use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

trait HasProjectThroughServer
{
    public function project(): HasOneThrough
    {
        return $this->hasOneThrough(
            Project::class,
            Server::class,
            'id',
            'id',
            'server_id',
            'project_id'
        );
    }
}
