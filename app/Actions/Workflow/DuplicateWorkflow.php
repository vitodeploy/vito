<?php

namespace App\Actions\Workflow;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\Validator;

class DuplicateWorkflow
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function duplicate(User $user, Workflow $workflow, array $input): Workflow
    {
        Validator::make($input, [
            'name' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $copy = $workflow->replicate(['user_id', 'name']);
        $copy->user_id = $user->id;
        $copy->name = ($input['name'] ?? null) ?: str($workflow->name.' (copy)')->limit(255, '')->toString();
        $copy->save();

        return $copy;
    }
}
