<?php

namespace App\ValidationRules;

use Cron\CronExpression;
use Illuminate\Contracts\Validation\Rule;

class CronRule implements Rule
{
    private bool $acceptCustom;

    public function __construct(bool $acceptCustom = false)
    {
        $this->acceptCustom = $acceptCustom;
    }

    public function passes($attribute, $value): bool
    {
        return CronExpression::isValidExpression($value) || ($this->acceptCustom && $value === 'custom');
    }

    public function message(): string
    {
        return __('Invalid frequency');
    }
}
