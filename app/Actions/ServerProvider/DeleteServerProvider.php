<?php

namespace App\Actions\ServerProvider;

use App\Models\ServerProvider;
use Exception;
use Illuminate\Validation\ValidationException;

class DeleteServerProvider
{
    /**
     * @throws Exception
     */
    public function delete(ServerProvider $serverProvider): void
    {
        if ($serverProvider->servers()->exists()) {
            throw ValidationException::withMessages([
                'provider' => 'This server provider is being used by a server.',
            ]);
        }

        $serverProvider->delete();
    }
}
