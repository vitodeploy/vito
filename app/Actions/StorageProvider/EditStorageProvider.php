<?php

namespace App\Actions\StorageProvider;

use App\Models\Project;
use App\Models\StorageProvider;
use Illuminate\Support\Facades\Validator;

class EditStorageProvider
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(StorageProvider $storageProvider, Project $project, array $input): StorageProvider
    {
        Validator::make($input, self::rules())->validate();

        $storageProvider->profile = $input['name'];
        $storageProvider->project_id = isset($input['global']) && $input['global'] ? null : $project->id;

        $storageProvider->save();

        return $storageProvider;
    }

    /**
     * @return array<string, mixed>
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
