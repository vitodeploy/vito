<?php

namespace App\Actions\Workflow;

use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\Validator;

class CreateWorkflow
{
    public function create(User $user, Project $project, array $input): Workflow
    {
        Validator::make($input, [
            'name' => ['string', 'max:255'],
        ])->validate();

        /** @var Workflow $workflow */
        $workflow = $project->workflows()->create([
            'user_id' => $user->id,
            'name' => $input['name'] ?? 'New Workflow',
        ]);

        return $workflow;
    }
}
