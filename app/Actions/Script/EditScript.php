<?php

namespace App\Actions\Script;

use App\Models\Script;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class EditScript
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(Script $script, User $user, array $input): Script
    {
        $this->validate($input);

        $script->name = $input['name'];
        $script->content = $input['content'];
        $script->project_id = isset($input['global']) && $input['global'] ? null : $user->current_project_id;

        $script->save();

        return $script;
    }

    private function validate(array $input): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];

        Validator::make($input, $rules)->validate();
    }
}
