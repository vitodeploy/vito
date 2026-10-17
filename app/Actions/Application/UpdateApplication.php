<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\User;

class UpdateApplication
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Application $application, array $input, User $actor): Application
    {
        app(ValidateApplication::class)->validate($application->project, $input, $actor, $application);

        $application->update([
            'name' => $input['name'],
            'source_control_id' => $input['source_control_id'],
            'repository' => $input['repository'],
        ]);

        return $application;
    }
}
