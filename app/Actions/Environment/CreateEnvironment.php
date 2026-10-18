<?php

namespace App\Actions\Environment;

use App\Enums\ApplicationStatus;
use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Environment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateEnvironment
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Application $application, array $input): Environment
    {
        return DB::transaction(function () use ($application, $input): Environment {
            $application = Application::query()->lockForUpdate()->findOrFail($application->id);
            app(ValidateEnvironment::class)->validate($application, $input);
            if ($application->status !== ApplicationStatus::READY) {
                throw ValidationException::withMessages(['name' => 'Environments cannot be added during teardown.']);
            }

            $environment = new Environment([
                'application_id' => $application->id,
                'name' => $input['name'],
                'branch' => $input['branch'],
                'runtime_version' => $input['runtime_version'] ?? null,
            ]);
            $environment->slug = Str::slug($input['name']);
            $environment->status = EnvironmentStatus::READY;
            $environment->save();

            return $environment;
        });
    }
}
