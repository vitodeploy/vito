<?php

namespace App\Traits;

use Carbon\Carbon;
use Exception;

/**
 * @property string $created_at_by_timezone
 * @property string $updated_at_by_timezone
 */
trait HasTimezoneTimestamps
{
    public function getCreatedAtByTimezoneAttribute(): string
    {
        return $this->getDateTimeByTimezone($this->created_at);
    }

    public function getUpdatedAtByTimezoneAttribute(): string
    {
        return $this->getDateTimeByTimezone($this->updated_at);
    }

    public function getDateTimeByTimezone(?Carbon $value = null): ?string
    {
        if ($value && auth()->user() && auth()->user()->timezone) {
            try {
                return date_with_timezone($value, auth()->user()->timezone);
            } catch (Exception) {
            }
        }

        return $value;
    }
}
