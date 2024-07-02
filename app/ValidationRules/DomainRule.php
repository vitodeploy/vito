<?php

namespace App\ValidationRules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DomainRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value) {
            return;
        }
        if (preg_match("/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/", $value) === 1) {
            return;
        }
        $fail('Domain is not valid')->translate();
    }
}
