<?php

namespace App\Actions\ServerIp;

use App\Enums\IpAddressFamily;
use App\Enums\IpAddressStatus;
use App\Enums\IpAddressType;
use App\Jobs\ServerIp\PersistServerIpsJob;
use App\Models\Server;
use App\Models\ServerIpAddress;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManageServerIp
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Server $server, array $input): ServerIpAddress
    {
        $this->validate($server, $input);

        $ip = (string) $input['ip'];
        $family = ServerIpAddress::familyFor($ip);

        $address = new ServerIpAddress([
            'ip' => $ip,
            'prefix_length' => $this->prefixLength($input, $family),
            'family' => $family,
            'interface' => $input['interface'],
            'type' => ServerIpAddress::classifyType($ip),
            'status' => IpAddressStatus::CONFIGURING,
            'is_managed' => true,
            'is_primary' => false,
        ]);
        $address->server_id = $server->id;
        $address->save();

        $this->queueApply($server);

        return $address;
    }

    public function setPrimary(ServerIpAddress $address): void
    {
        $server = $address->server;

        DB::transaction(function () use ($server, $address): void {
            if ($address->type === IpAddressType::PRIVATE) {
                $server->local_ip = $address->ip;
            } else {
                $server->ip = $address->ip;
            }
            $server->save();

            $primaryIps = array_filter([$server->ip, $server->local_ip]);
            $server->ipAddresses()->update(['is_primary' => false]);
            $server->ipAddresses()->whereIn('ip', $primaryIps)->update(['is_primary' => true]);
        });
    }

    public function delete(ServerIpAddress $address): void
    {
        if (! $address->is_managed) {
            throw ValidationException::withMessages([
                'ip' => __('Only manually added IP addresses can be removed.'),
            ]);
        }

        $address->status = IpAddressStatus::DELETING;
        $address->save();

        $this->queueApply($address->server);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'ip' => [
                'required',
                'ip',
                Rule::unique('server_ip_addresses', 'ip')->where('server_id', $server->id),
            ],
            'interface' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Za-z0-9@._-]+$/',
            ],
            'prefix_length' => [
                'nullable',
                'integer',
                'min:1',
                'max:128',
                function (string $attribute, mixed $value, Closure $fail) use ($input): void {
                    $ip = $input['ip'] ?? null;
                    if (is_string($ip) && ! str_contains($ip, ':') && (int) $value > 32) {
                        $fail(__('The prefix length must not be greater than 32 for an IPv4 address.'));
                    }
                },
            ],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function prefixLength(array $input, IpAddressFamily $family): int
    {
        $value = $input['prefix_length'] ?? null;

        if ($value === null || $value === '') {
            return $family === IpAddressFamily::V6 ? 64 : 32;
        }

        return (int) $value;
    }

    private function queueApply(Server $server): void
    {
        dispatch(new PersistServerIpsJob($server))->onQueue('ssh');
    }
}
