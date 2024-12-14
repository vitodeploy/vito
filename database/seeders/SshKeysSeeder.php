<?php

namespace Database\Seeders;

use App\Enums\SshKeyStatus;
use App\Models\Server;
use App\Models\SshKey;
use Illuminate\Database\Seeder;

class SshKeysSeeder extends Seeder
{
    public function run(): void
    {
        $servers = Server::all();

        foreach ($servers as $server) {
            $sshKey = SshKey::factory()->create([
                'user_id' => $server->user_id,
                'name' => 'id_rsa',
                'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQDZ',
            ]);
            $server->sshKeys()->attach($sshKey, [
                'status' => SshKeyStatus::ADDED,
            ]);
        }
    }
}
