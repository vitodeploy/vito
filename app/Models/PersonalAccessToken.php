<?php

namespace App\Models;

use App\Traits\HasTimezoneTimestamps;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * @property int $id
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property string $name
 * @property string $token
 * @property array $abilities
 * @property Carbon $last_used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasTimezoneTimestamps;
}
