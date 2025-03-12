<?php

namespace App\Actions\Script;

use App\Models\Script;
use App\Models\User;

class EditScript
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(Script $script, User $user, array $input): Script
    {
        $script->name = $input['name'];
        $script->content = $input['content'];
        $script->project_id = isset($input['global']) && $input['global'] ? null : $user->current_project_id;

        $script->save();

        return $script;
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }
}
