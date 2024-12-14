<?php

namespace Database\Seeders;

use App\Models\ServerProvider;
use Illuminate\Database\Seeder;

class ServerProvidersSeeder extends Seeder
{
    public function run(): void
    {
        ServerProvider::factory()->create([
            'profile' => 'AWS',
            'provider' => 'aws',
            'credentials' => [
                'key' => 'aws_key',
                'secret' => 'aws_secret',
            ],
            'connected' => 1,
        ]);

        ServerProvider::factory()->create([
            'profile' => 'Digital Ocean',
            'provider' => 'digitalocean',
            'credentials' => [
                'token' => 'do_token',
            ],
            'connected' => 1,
        ]);

        ServerProvider::factory()->create([
            'profile' => 'Linode',
            'provider' => 'linode',
            'credentials' => [
                'token' => 'linode_token',
            ],
            'connected' => 1,
        ]);

        ServerProvider::factory()->create([
            'profile' => 'Vultr',
            'provider' => 'vultr',
            'credentials' => [
                'token' => 'vultr_token',
            ],
            'connected' => 1,
        ]);

        ServerProvider::factory()->create([
            'profile' => 'Hetzner',
            'provider' => 'hetzner',
            'credentials' => [
                'token' => 'hetzner_token',
            ],
            'connected' => 1,
        ]);
    }
}
