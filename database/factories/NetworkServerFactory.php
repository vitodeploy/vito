<?php

namespace Database\Factories;

use App\Actions\Network\GenerateWireGuardKeys;
use App\Enums\NetworkServerStatus;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NetworkServer>
 */
class NetworkServerFactory extends Factory
{
    protected $model = NetworkServer::class;

    public function definition(): array
    {
        $keys = app(GenerateWireGuardKeys::class)->generate();

        return [
            'network_id' => Network::factory(),
            'server_id' => Server::factory(),
            'server_ip_address_id' => null,
            'ip' => $this->hostAddress(),
            'public_key' => $keys['public_key'],
            'private_key' => $keys['private_key'],
            'status' => NetworkServerStatus::ACTIVE,
            'sync_attempts' => 0,
        ];
    }

    /**
     * A two-digit host pattern gives faker's unique() only 100 values, which it can exhaust
     * across a long-running process and then throw. This spans the whole 100.64.0.0/16.
     */
    private function hostAddress(): string
    {
        $host = $this->faker->unique()->numberBetween(1, 64516);

        return '100.64.'.intdiv($host - 1, 254).'.'.(($host - 1) % 254 + 1);
    }
}
