<?php

namespace App\Actions\ServerProvider;

use App\Models\ServerProvider;
use Illuminate\Validation\ValidationException;

class DeleteServerProvider
{
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
