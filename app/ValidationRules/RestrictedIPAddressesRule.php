<?php

namespace App\ValidationRules;

use App\Support\Cidr;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RestrictedIPAddressesRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && self::isRestricted($value)) {
            $fail('IP address is restricted')->translate();
        }
    }

    public static function isRestricted(string $ip): bool
    {
        foreach (config('core.restricted_ip_addresses') as $restricted) {
            if ($ip === $restricted || Cidr::contains($restricted, $ip)) {
                return true;
            }
        }

        return false;
    }
}
