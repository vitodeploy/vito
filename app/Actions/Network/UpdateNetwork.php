<?php

namespace App\Actions\Network;

use App\Models\Network;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateNetwork
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Network $network, array $input): Network
    {
        $this->validate($network, $input);

        $network->update([
            'name' => $input['name'] ?? $network->name,
        ]);

        return $network->refresh();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Network $network, array $input): void
    {
        Validator::make($input, [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('networks', 'name')
                    ->where('project_id', $network->project_id)
                    ->ignore($network->id),
            ],
        ])->validate();
    }
}
