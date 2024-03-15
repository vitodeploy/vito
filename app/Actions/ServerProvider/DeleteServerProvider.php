<?php

namespace App\Actions\ServerProvider;

use App\Models\ServerProvider;
use Exception;

class DeleteServerProvider
{
    /**
     * @throws Exception
     */
    public function delete(ServerProvider $serverProvider): void
    {
        if ($serverProvider->servers()->exists()) {
            throw new Exception('This server provider is being used by a server.');
        }

        $serverProvider->delete();
    }
}
