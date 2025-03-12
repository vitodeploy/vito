<?php

namespace App\Actions\ServerProvider;

use App\Models\Project;
use App\Models\ServerProvider;

class EditServerProvider
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(ServerProvider $serverProvider, Project $project, array $input): ServerProvider
    {
        $serverProvider->profile = $input['name'];
        $serverProvider->project_id = isset($input['global']) && $input['global'] ? null : $project->id;

        $serverProvider->save();

        return $serverProvider;
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'name' => [
                'required',
            ],
        ];
    }
}
