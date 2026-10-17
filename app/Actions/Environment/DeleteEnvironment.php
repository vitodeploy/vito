<?php

namespace App\Actions\Environment;

use App\Models\Environment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DeleteEnvironment
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function delete(Environment $environment, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', Rule::in([$environment->name])],
        ])->validate();

        $environment->delete();
    }
}
