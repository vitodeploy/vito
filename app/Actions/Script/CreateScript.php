<?php

namespace App\Actions\Script;

use App\Models\Script;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateScript
{
    public function create(User $user, array $input): Script
    {
        $this->validate($input);

        $script = new Script([
            'user_id' => $user->id,
            'name' => $input['name'],
            'content' => $input['content'],
        ]);
        $script->save();

        return $script;
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ])->validate();
    }
}
