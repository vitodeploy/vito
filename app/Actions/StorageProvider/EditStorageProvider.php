<?php

namespace App\Actions\StorageProvider;

use App\Models\Project;
use App\Models\StorageProvider;
use Illuminate\Validation\ValidationException;

class EditStorageProvider
{
    public function edit(StorageProvider $storageProvider, Project $project, array $input): StorageProvider
    {
        $storageProvider->profile = $input['name'];
        $storageProvider->project_id = isset($input['global']) && $input['global'] ? null : $project->id;

        $storageProvider->save();

        return $storageProvider;
    }

    /**
     * @throws ValidationException
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
