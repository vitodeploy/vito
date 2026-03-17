<?php

namespace App\Actions\Application;

use App\Models\Application;

class ResetTemplate
{
    public function reset(Application $application): void
    {
        $application->custom_template = null;
        $application->save();

        app(DeployApplication::class)->deploy($application);
    }
}
