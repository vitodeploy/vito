<?php

namespace App\Actions\Console;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GenerateEventsToken
{
    /**
     * @return array{token: string}
     */
    public function generate(User $user): array
    {
        $token = Str::random(64);

        Cache::put("events_token:{$token}", [
            'user_id' => $user->id,
            'project_id' => $user->current_project_id,
        ], 30);

        return ['token' => $token];
    }

    /**
     * @return array{user_id: int, project_id: int}|null
     */
    public function validate(string $token): ?array
    {
        return Cache::pull("events_token:{$token}");
    }
}
