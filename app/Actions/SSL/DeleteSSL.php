<?php

namespace App\Actions\SSL;

use App\Models\Ssl;

class DeleteSSL
{
    public function delete(Ssl $ssl): void
    {
        $ssl->site->server->webserver()->handler()->removeSSL($ssl);
        $ssl->delete();
    }
}
