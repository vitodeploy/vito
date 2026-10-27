<?php

namespace App\Actions\Network;

use App\Enums\IpAddressStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Support\Cidr;
use Illuminate\Validation\ValidationException;

class ResolveServerAddress
{
    public function between(Server $client, Server $target): string
    {
        return $this->connection($client, $target)['ip'] ?? throw ValidationException::withMessages([
            'backends' => 'The servers must share an available private network.',
        ]);
    }

    /** @return ?array{network_id: ?int, ip: string, client_ip: string} */
    public function connection(Server $client, Server $target): ?array
    {
        if ($client->is($target)) {
            return ['network_id' => null, 'ip' => '127.0.0.1', 'client_ip' => '127.0.0.1'];
        }
        if ($client->project_id === $target->project_id) {
            $members = NetworkServer::query()->whereIn('server_id', [$client->id, $target->id])
                ->whereIn('status', [NetworkServerStatus::ACTIVE, NetworkServerStatus::UPDATING])
                ->whereHas('network', fn ($query) => $query->where('project_id', $client->project_id)->where('status', '!=', NetworkStatus::DELETING))
                ->with('serverIpAddress')->orderBy('network_id')->get()->groupBy('network_id');
            foreach ($members as $networkId => $pair) {
                $addresses = $pair->mapWithKeys(fn (NetworkServer $member): array => [
                    $member->server_id => $member->server_ip_address_id !== null
                        ? ($member->serverIpAddress?->status === IpAddressStatus::CONFIGURED ? $member->serverIpAddress->ip : null) : $member->ip,
                ]);
                $from = $addresses->get($client->id);
                $to = $addresses->get($target->id);
                if (is_string($from) && is_string($to) && Cidr::isValidAddress($from) && Cidr::isValidAddress($to)
                    && Cidr::bits($from) === Cidr::bits($to)) {
                    return ['network_id' => $networkId, 'ip' => $to, 'client_ip' => $from];
                }
            }
        }

        return null;
    }
}
