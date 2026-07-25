<?php

namespace App\ValidationRules;

use App\Support\Cidr;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A network's declared range is what its firewall rules are scoped to, so a range wider than
 * private address space would emit `allow from 0.0.0.0/0` on every member — opening the ports
 * to the internet on servers that were default-deny.
 */
class PrivateRangeRule implements ValidationRule
{
    private const BLOCKS = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '100.64.0.0/10',
        'fc00::/7',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '' || ! Cidr::isValid($value)) {
            return;
        }

        foreach (self::BLOCKS as $block) {
            if (Cidr::containsRange($block, $value)) {
                return;
            }
        }

        $fail('The :attribute must be a private range inside 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 100.64.0.0/10 or fc00::/7.')->translate();
    }
}
