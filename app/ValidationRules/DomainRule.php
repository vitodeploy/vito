<?php

namespace App\ValidationRules;

use Illuminate\Contracts\Validation\Rule;

class DomainRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if ($value) {
            return preg_match("/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/", $value);
        }

        return true;
    }

    public function message(): string
    {
        return __('Domain is not valid');
    }
}
