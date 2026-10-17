<?php

namespace App\Actions\Application;

use App\Models\Application;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DeleteApplication
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function delete(Application $application, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', Rule::in([$application->name])],
        ])->validate();

        $application->delete();
    }
}
