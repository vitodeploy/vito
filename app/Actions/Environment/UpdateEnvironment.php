<?php

namespace App\Actions\Environment;

use App\Models\Environment;

class UpdateEnvironment
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Environment $environment, array $input): Environment
    {
        app(ValidateEnvironment::class)->validate($environment->application, $input, $environment);

        $environment->update([
            'name' => $input['name'],
            'branch' => $input['branch'],
            'runtime_version' => $input['runtime_version'] ?? null,
        ]);

        return $environment;
    }
}
