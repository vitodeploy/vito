<?php

namespace App\ValidationRules;

use Illuminate\Contracts\Validation\Rule;

class SshKeyRule implements Rule
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
        $key_parts = explode(' ', $value, 3);
        if (count($key_parts) < 2) {
            return false;
        }
        if (count($key_parts) > 3) {
            return false;
        }
        $algorithm = $key_parts[0];
        $key = $key_parts[1];
        if (! in_array($algorithm, ['ssh-rsa', 'ssh-dss'])) {
            return false;
        }
        $key_base64_decoded = base64_decode($key, true);
        if ($key_base64_decoded == false) {
            return false;
        }
        $check = base64_decode(substr($key, 0, 16));
        $check = preg_replace("/[^\w\-]/", '', $check);
        if ((string) $check !== (string) $algorithm) {
            return false;
        }

        return true;
    }

    /**
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    public function message()
    {
        return __('Invalid key');
    }
}
