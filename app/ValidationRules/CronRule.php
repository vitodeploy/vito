<?php

namespace App\ValidationRules;

use Closure;
use Cron\CronExpression;
use Illuminate\Contracts\Validation\ValidationRule;

class CronRule implements ValidationRule
{
    public function __construct(private readonly bool $acceptCustom = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (CronExpression::isValidExpression($value)) {
            return;
        }
        if ($this->acceptCustom && $value === 'custom') {
            return;
        }
        $fail('Invalid frequency')->translate();
    }
}
