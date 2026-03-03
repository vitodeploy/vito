<?php

namespace App\Actions\Console;

use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GenerateTerminalToken
{
    /**
     * Generate a short-lived one-time token for WebSocket terminal authentication.
     *
     * @return array{token: string}
     */
    public function generate(Server $server, User $user, string $sshUser): array
    {
        $token = Str::random(64);

        Cache::put("terminal_token:{$token}", [
            'server_id' => $server->id,
            'user_id' => $user->id,
            'ssh_user' => $sshUser,
        ], 30);

        return ['token' => $token];
    }

    /**
     * Validate and consume a terminal token.
     *
     * @return array{server_id: int, user_id: int, ssh_user: string}|null
     */
    public function validate(string $token): ?array
    {
        return Cache::pull("terminal_token:{$token}");
    }
}
