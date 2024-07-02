<?php

namespace App\ValidationRules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RestrictedIPAddressesRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, config('core.restricted_ip_addresses'))) {
            return;
        }
        $fail('IP address is restricted')->translate();
    }
}
