<?php

namespace App\Actions\ServerProvider;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\ServerProviderResource;
use App\Models\ServerProvider;
use Illuminate\Support\Facades\Validator;

class EditServerProvider
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(ServerProvider $serverProvider, array $input, ?int $projectId): ServerProvider
    {
        Validator::make($input, [
            'name' => [
                'required',
            ],
        ])->validate();

        $serverProvider->profile = $input['name'];
        $serverProvider->project_id = $projectId;

        $serverProvider->save();

        SocketEvent::dispatch(new SocketEventDTO(
            $serverProvider->project_id ?? 0,
            'server-provider.updated',
            new ServerProviderResource($serverProvider),
        ));

        return $serverProvider;
    }
}
