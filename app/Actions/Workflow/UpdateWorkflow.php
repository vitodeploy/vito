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
            'nodes' => ['nullable', 'array', 'max:500'],
            'nodes.*.id' => ['required', 'string', 'max:255', 'distinct'],
            'edges' => ['nullable', 'array', 'max:2000'],
            'edges.*.source' => ['required', 'string', 'max:255'],
            'edges.*.target' => ['required', 'string', 'max:255'],
        ])->validate();

        $workflow->payload = [
            'nodes' => $input['nodes'],
            'edges' => $input['edges'],
        ];

        if (! $workflow->getExecutionTree()) {
            throw ValidationException::withMessages([
                'nodes' => 'Starting node not found',
            ]);
        }

        $workflow->name = $input['name'];
        $workflow->save();

        return $workflow;
    }
}
