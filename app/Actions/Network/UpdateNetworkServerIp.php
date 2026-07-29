<?php

namespace App\Actions\Network;

use App\Enums\IpAddressType;
use App\Models\NetworkServer;
use App\ValidationRules\WithinCidrRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateNetworkServerIp
{
    public function __construct(private ApplyNetworkFirewall $firewall) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(NetworkServer $member, array $input): NetworkServer
    {
        $this->validate($member, $input);

        $member->update(['server_ip_address_id' => $input['server_ip_address_id']]);

        $this->firewall->handle($member->network);

        return $member->refresh();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(NetworkServer $member, array $input): void
    {
        Validator::make($input, [
            'server_ip_address_id' => [
                'required',
                Rule::exists('server_ip_addresses', 'id')
                    ->where('server_id', $member->server_id)
                    ->where('type', IpAddressType::PRIVATE->value),
                Rule::unique('network_servers', 'server_ip_address_id')->ignore($member->id),
                new WithinCidrRule($member->network->cidr),
            ],
        ])->validate();
    }
}
