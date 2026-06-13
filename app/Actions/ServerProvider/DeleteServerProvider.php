<?php

namespace App\Actions\ServerProvider;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
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

        $id = $serverProvider->id;
        $projectId = $serverProvider->project_id ?? 0;

        $serverProvider->delete();

        SocketEvent::dispatch(new SocketEventDTO(
            $projectId,
            'server-provider.deleted',
            ['id' => $id],
        ));
    }
}
