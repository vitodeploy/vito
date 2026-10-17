<?php

namespace App\Actions\Environment;

use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Environment;
use Illuminate\Support\Str;

class CreateEnvironment
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Application $application, array $input): Environment
    {
        app(ValidateEnvironment::class)->validate($application, $input);

        $environment = new Environment([
            'application_id' => $application->id,
            'name' => $input['name'],
            'slug' => Str::slug($input['name']),
            'branch' => $input['branch'],
            'runtime_version' => $input['runtime_version'] ?? null,
        ]);
        $environment->status = EnvironmentStatus::READY;
        $environment->save();

        return $environment;
    }
}
