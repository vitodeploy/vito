<?php

namespace App\Actions\Site;

use App\Models\Site;

class DeleteSite
{
    public function delete(Site $site): void
    {
        $site->server->webserver()->handler()->deleteSite($site);
        $site->delete();
    }
}
