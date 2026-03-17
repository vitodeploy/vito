<?php

namespace App\Actions\Application;

use App\Models\Application;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateTemplate
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(Application $application, array $input): void
    {
        Validator::make($input, [
            'template' => ['required', 'string'],
        ])->validate();

        $application->custom_template = $input['template'];
        $application->save();

        app(DeployApplication::class)->deploy($application);
    }
}
