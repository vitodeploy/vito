<?php

namespace App\ValidationRules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Exception\NoKeyLoadedException;

class SshKeyRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            PublicKeyLoader::load($value);

            return;
        } catch (NoKeyLoadedException) {
            $fail('Invalid key')->translate();
        }
    }
}
