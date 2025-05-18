<?php

namespace App\Actions\ServerProvider;

use App\Models\Project;
use App\Models\ServerProvider;
use Illuminate\Support\Facades\Validator;

class EditServerProvider
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(ServerProvider $serverProvider, Project $project, array $input): ServerProvider
    {
        Validator::make($input, self::rules())->validate();

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
