<?php

namespace App\Actions\Script;

use App\Models\Script;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateScript
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(User $user, array $input): Script
    {
        $this->validate($input);

        $script = new Script([
            'user_id' => $user->id,
            'name' => $input['name'],
            'content' => $input['content'],
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->current_project_id,
        ]);
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
