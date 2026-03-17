<?php

namespace App\Actions\Application;

use App\Models\Application;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateApplication
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(Application $application, array $input): Application
    {
        Validator::make($input, $application->type()->editRules($input))->validate();

        $application->type()->update($input);
        $application->refresh();

        app(DeployApplication::class)->deploy($application);

        return $application;
    }
}
