<?php

namespace App\ValidationRules;

use Cron\CronExpression;
use Illuminate\Contracts\Validation\ValidationRule;

class CronRule implements ValidationRule
{
    private bool $acceptCustom;

    public function __construct(bool $acceptCustom = false)
    {
        $this->acceptCustom = $acceptCustom;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
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
