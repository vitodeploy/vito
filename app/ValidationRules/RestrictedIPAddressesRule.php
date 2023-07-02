<?php

namespace App\ValidationRules;

use Illuminate\Contracts\Validation\Rule;

class RestrictedIPAddressesRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return ! in_array($value, config('core.restricted_ip_addresses'));
    }

    /**
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    public function message()
    {
        return __('IP address is restricted.');
    }
}
