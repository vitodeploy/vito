<?php

namespace App\Actions\Network;

use App\Models\Network;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateNetwork
{
    public function __construct(private ApplyNetworkFirewall $apply) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Network $network, array $input): Network
    {
        $this->validate($network, $input);

        $firewallEnabled = array_key_exists('firewall_enabled', $input)
            ? (bool) $input['firewall_enabled']
            : $network->firewall_enabled;

        $firewallChanged = $firewallEnabled !== $network->firewall_enabled;

        $network->update([
            'name' => $input['name'] ?? $network->name,
            'firewall_enabled' => $firewallEnabled,
        ]);

        if ($firewallChanged) {
            $this->apply->handle($network);
        }

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
            'firewall_enabled' => ['sometimes', 'boolean'],
        ])->validate();
    }
}
