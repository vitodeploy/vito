<?php

namespace App\ValidationRules;

use Illuminate\Contracts\Validation\Rule;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Exception\NoKeyLoadedException;

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
        try {
            PublicKeyLoader::load($value);

            return true;
        } catch (NoKeyLoadedException $e) {
            return false;
        }
    }

    /**
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    public function message()
    {
        return __('Invalid key');
    }
}
