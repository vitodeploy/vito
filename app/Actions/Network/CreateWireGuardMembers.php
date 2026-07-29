<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Models\Network;
use App\Models\Server;
use App\Support\Cidr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CreateWireGuardMembers
{
    public function __construct(private GenerateWireGuardKeys $keys) {}

    /**
     * Allocate the next free host address and a fresh key pair for each server,
     * and create its membership row.
     *
     * @param  Collection<int, Server>  $servers
     * @param  array<int, string>  $used
     * @return array<int, int>
     */
    public function create(Network $network, Collection $servers, array $used = []): array
    {
        $ids = [];

        foreach ($servers as $server) {
            $ip = Cidr::nextHost((string) $network->cidr, $used);

            if ($ip === null) {
                throw ValidationException::withMessages([
                    'servers' => __('The network address block is full.'),
                ]);
            }

            $used[] = $ip;

            $keys = $this->keys->generate();

            $member = $network->servers()->create([
                'server_id' => $server->id,
                'ip' => $ip,
                'public_key' => $keys['public_key'],
                'private_key' => $keys['private_key'],
                'status' => NetworkServerStatus::PENDING,
            ]);

            $ids[] = $member->id;
        }

        return $ids;
    }
}
