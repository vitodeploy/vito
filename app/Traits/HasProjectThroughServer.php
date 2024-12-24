<?php

namespace App\Traits;

use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

trait HasProjectThroughServer
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough<\App\Models\Project, \App\Models\Server, $this>
     */
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
