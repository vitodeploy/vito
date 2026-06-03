<?php

namespace App\Actions\ServerIp;

use App\DTOs\SocketEventDTO;
use App\Enums\IpAddressFamily;
use App\Enums\IpAddressStatus;
use App\Enums\IpAddressType;
use App\Events\SocketEvent;
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
    private const MAX_RANGE = 256;

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Server $server, array $input): void
    {
        $this->validate($server, $input);

        $created = DB::transaction(function () use ($server, $input): bool {
            $created = false;
            foreach ($this->expandIps($input) as $ip) {
                if ($server->ipAddresses()->where('ip', $ip)->exists()) {
                    continue;
                }

                $family = ServerIpAddress::familyFor($ip);

                $address = new ServerIpAddress([
                    'ip' => $ip,
                    'prefix_length' => $this->prefixLength($input, $family),
                    'family' => $family,
                    'interface' => $input['interface'],
                ]);
                $address->server_id = $server->id;
                $address->type = ServerIpAddress::classifyType($ip);
                $address->status = IpAddressStatus::CONFIGURING;
                $address->is_managed = true;
                $address->is_primary = false;
                $address->save();

                $created = true;
            }

            return $created;
        });

        if ($created) {
            $this->queueApply($server);
        }
    }

    /**
     * Sets the address as the server's primary. The server must be saved before
     * the bulk is_primary update so the latter reads the new ip/local_ip.
     */
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

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $server->project_id,
            type: 'server-ip.updated',
            data: ['server_id' => $server->id],
        ));
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
        $isRange = $this->isRange($input);

        $rules = [
            'ip' => ['required', 'ip'],
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
        ];

        if ($isRange) {
            $rules['ip_last'] = ['required', 'ip', $this->rangeRule($input)];
        } else {
            $rules['ip'][] = Rule::unique('server_ip_addresses', 'ip')->where('server_id', $server->id);
        }

        Validator::make($input, $rules)->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function rangeRule(array $input): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($input): void {
            $first = $input['ip'] ?? null;
            if (! is_string($first) || ! is_string($value)) {
                return;
            }

            $start = @inet_pton($first);
            $end = @inet_pton($value);
            if ($start === false || $end === false) {
                return;
            }

            if (strlen($start) !== strlen($end)) {
                $fail(__('The first and last address must be the same IP version.'));

                return;
            }

            if (strcmp($start, $end) > 0) {
                $fail(__('The first address must not be greater than the last address.'));

                return;
            }

            if ($this->rangeCount($start, $end) > self::MAX_RANGE) {
                $fail(__('The range is too large; at most :max addresses can be added at once.', ['max' => self::MAX_RANGE]));
            }
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    private function expandIps(array $input): array
    {
        $first = (string) $input['ip'];

        if (! $this->isRange($input)) {
            return [$first];
        }

        $current = (string) inet_pton($first);
        $end = (string) inet_pton((string) $input['ip_last']);

        $ips = [];
        while (true) {
            $address = inet_ntop($current);
            if ($address !== false) {
                $ips[] = $address;
            }
            if ($current === $end) {
                break;
            }
            $current = $this->incrementBinary($current);
        }

        return $ips;
    }

    private function rangeCount(string $start, string $end): int
    {
        $count = 1;
        $current = $start;
        while ($current !== $end) {
            $current = $this->incrementBinary($current);
            $count++;
            if ($count > self::MAX_RANGE) {
                break;
            }
        }

        return $count;
    }

    private function incrementBinary(string $binary): string
    {
        for ($i = strlen($binary) - 1; $i >= 0; $i--) {
            $byte = ord($binary[$i]);
            if ($byte < 255) {
                $binary[$i] = chr($byte + 1);

                return $binary;
            }
            $binary[$i] = chr(0);
        }

        return $binary;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function isRange(array $input): bool
    {
        return isset($input['ip_last']) && is_string($input['ip_last']) && $input['ip_last'] !== '';
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
