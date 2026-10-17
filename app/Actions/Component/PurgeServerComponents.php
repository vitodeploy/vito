<?php

namespace App\Actions\Component;

use App\Models\Server;
use Illuminate\Support\Facades\DB;

class PurgeServerComponents
{
    /**
     * @return array<int, int>
     */
    public function purge(Server $server): array
    {
        return DB::transaction(function () use ($server): array {
            $environmentIds = $server->components()->distinct()->pluck('environment_id')->all();

            $server->components()->delete();

            return $environmentIds;
        });
    }

    /**
     * Post-delete extension point for resynchronizing affected environments on surviving servers.
     *
     * @param  array<int, int>  $environmentIds
     */
    public function handle(array $environmentIds): void {}
}
