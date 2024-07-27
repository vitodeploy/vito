<?php

namespace App\Actions\Site;

use App\Exceptions\SSHUploadFailed;
use App\Models\Site;

class UpdateEnv
{
    /**
     * @throws SSHUploadFailed
     */
    public function update(Site $site, array $input): void
    {
        $site->server->os()->editFile(
            $site->path.'/.env',
            $input['env']
        );
    }
}
