<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Database\Seeder;

class ServerLogsSeeder extends Seeder
{
    public function run(): void
    {
        $servers = Server::all();
        foreach ($servers as $server) {
            ServerLog::log($server, 'install-php', 'PHP 7.4 installed');
            ServerLog::log($server, 'install-nginx', 'Nginx installed');
            ServerLog::factory()->create([
                'server_id' => $server->id,
                'type' => 'remote',
                'name' => '/var/log/nginx/error.log',
                'disk' => 'ssh',
                'is_remote' => true,
            ]);
        }
    }
}
