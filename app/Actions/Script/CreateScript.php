<?php

namespace App\Actions\Script;

use App\Models\Script;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateScript
{
    /**
     * @throws ValidationException
     */
    public function handle(User $creator, array $input): Script
    {
        $this->validateInputs($input);

        $script = new Script([
            'user_id' => $creator->id,
            'name' => $input['name'],
            'content' => $input['content'],
        ]);
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

        Validator::make($input, $rules)->validateWithBag('createScript');
    }
}
