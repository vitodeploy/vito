<?php

namespace App\ValidationRules;

use App\Models\ServerIpAddress;
use App\Support\Cidr;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WithinCidrRule implements ValidationRule
{
    public function __construct(private ?string $cidr) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->cidr === null || $this->cidr === '' || ! Cidr::isValid($this->cidr)) {
            return;
        }

        $ip = ServerIpAddress::query()->whereKey($value)->value('ip');

        if (! is_string($ip)) {
            return;
        }

        if (! Cidr::contains($this->cidr, $ip)) {
            $fail('The selected address :ip is outside the network range :cidr.')->translate([
                'ip' => $ip,
                'cidr' => $this->cidr,
            ]);
        }
    }
}
