<?php

namespace App\Actions\Script;

use App\Models\Script;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateScript
{
    /**
     * @throws ValidationException
     */
    public function handle(Script $script, array $input): Script
    {
        $this->validateInputs($input);

        $script->name = $input['name'];
        $script->content = $input['content'];
        $script->save();

        return $script;
    }

    /**
     * @throws ValidationException
     */
    private function validateInputs(array $input): void
    {
        $rules = [
            'name' => 'required',
            'content' => 'required',
        ];

        Validator::make($input, $rules)->validateWithBag('updateScript');
    }
}
