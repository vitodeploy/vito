<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;

class UpdateEnv
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        $site->server->os()->editFileAs(
            $site->path.'/.env',
            $site->user,
            trim((string) $input['env']),
        );
    }
}
