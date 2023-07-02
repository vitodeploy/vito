<?php

namespace App\ValidationRules;

use Cron\CronExpression;
use Illuminate\Contracts\Validation\Rule;

class CronRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        return CronExpression::isValidExpression($value);
    }

    public function message(): string
    {
        return __('Invalid frequency');
    }
}
