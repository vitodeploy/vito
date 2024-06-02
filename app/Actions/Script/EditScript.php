<?php

namespace App\Actions\Script;

use App\Models\Script;
use Illuminate\Support\Facades\Validator;

class EditScript
{
    public function edit(Script $script, array $input): Script
    {
        $this->validate($input);

        $script->name = $input['name'];
        $script->content = $input['content'];
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
