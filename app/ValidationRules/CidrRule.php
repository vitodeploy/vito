<?php

namespace App\ValidationRules;

use App\Support\Cidr;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CidrRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! Cidr::isValid($value)) {
            $fail('The :attribute must be a valid IPv4 or IPv6 CIDR, for example 10.0.0.0/24 or fd00::/64.')->translate();
        }
    }
}
