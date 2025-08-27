<?php

namespace App\ValidationRules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TimezoneRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value) {
            return;
        }

        if (! in_array($value, timezone_identifiers_list())) {
            $fail('The selected timezone is invalid.')->translate();
        }
    }
}
