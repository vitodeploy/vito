<?php

namespace App\Actions\ServerProvider;

use App\Models\ServerProvider;
use Illuminate\Support\Facades\Validator;

class EditServerProvider
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(ServerProvider $serverProvider, array $input): ServerProvider
    {
        Validator::make($input, [
            'name' => [
                'required',
            ],
        ])->validate();

        $serverProvider->profile = $input['name'];
        $serverProvider->project_id = isset($input['global']) && $input['global'] ? null : $serverProvider->user->currentProject?->id;

        $serverProvider->save();

        return $serverProvider;
    }
}
