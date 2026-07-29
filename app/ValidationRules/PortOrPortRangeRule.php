<?php

namespace App\ValidationRules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PortOrPortRangeRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^[1-9]\d{0,4}(:[1-9]\d{0,4})?$/', $value) !== 1) {
            $fail('The :attribute must be a port (e.g. 8080) or a range (e.g. 3000:3010).')->translate();

            return;
        }

        $parts = array_map('intval', explode(':', $value));
        foreach ($parts as $p) {
            if ($p < 1 || $p > 65535) {
                $fail('The :attribute must be between 1 and 65535.')->translate();

                return;
            }
        }

        if (count($parts) === 2 && $parts[0] >= $parts[1]) {
            $fail('The :attribute range start must be lower than the range end.')->translate();
        }
    }
}
