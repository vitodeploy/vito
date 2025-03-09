<?php

namespace App\Actions\Script;

use App\Models\Script;
use App\Models\User;

class CreateScript
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(User $user, array $input): Script
    {
        $script = new Script([
            'user_id' => $user->id,
            'name' => $input['name'],
            'content' => $input['content'],
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->current_project_id,
        ]);
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
