<?php

namespace App\Actions\Site;

use App\Models\Site;

class UpdateEnv
{
    public function update(Site $site, array $input): void
    {
        $env = str_replace('"', '\"', $input['env']);
        $env = str_replace('${', '\${', $env);

        $site->server->os()->editFile(
            $site->path.'/.env',
            $env
        );
    }
}
