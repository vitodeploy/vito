<?php

namespace App\Actions\Workflow;

use App\Models\Workflow;
use Illuminate\Support\Facades\Validator;

class UpdateWorkflow
{
    public function update(Workflow $workflow, array $input): Workflow
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'nodes' => ['nullable', 'array'],
            'edges' => ['nullable', 'array'],
            'is_draft' => ['required', 'boolean'],
        ])->validate();

        $workflow->payload = [
            'nodes' => $input['nodes'],
            'edges' => $input['edges'],
        ];

        $workflow->name = $input['name'];
        $workflow->is_draft = $input['is_draft'];
        $workflow->save();

        return $workflow;
    }
}
