<?php

namespace App\Actions\Workflow;

use App\Models\Workflow;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateWorkflow
{
    public function update(Workflow $workflow, array $input): Workflow
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'nodes' => ['nullable', 'array'],
            'edges' => ['nullable', 'array'],
        ])->validate();

        $workflow->payload = [
            'nodes' => $input['nodes'],
            'edges' => $input['edges'],
        ];

        if (! $workflow->getStartingNode()) {
            throw ValidationException::withMessages([
                'nodes' => 'Starting node not found',
            ]);
        }

        $workflow->name = $input['name'];
        $workflow->save();

        return $workflow;
    }
}
