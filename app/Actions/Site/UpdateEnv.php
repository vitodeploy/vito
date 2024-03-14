<?php

namespace App\Actions\Site;

use App\Models\Site;

class UpdateEnv
{
    public function update(Site $site, array $input): void
    {
        $site->server->os()->editFile(
            $site->path.'/.env',
            $input['env']
        );
    }
}
