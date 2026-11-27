<?php

namespace App\ValidationRules;

use App\Support\Cidr;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects URLs whose host resolves to a loopback, private, link-local or reserved address.
 * After validation, `options()` pins the request to the checked address so a second DNS
 * lookup cannot redirect it to an internal host.
 */
class PublicUrlRule implements ValidationRule
{
    private const NON_PUBLIC_BLOCKS = [
        '100.64.0.0/10',
        '192.0.0.0/24',
        '198.18.0.0/15',
        '2002::/16',
        '64:ff9b::/96',
    ];

    private ?string $pin = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = is_string($value) ? parse_url($value) : false;
        $host = is_array($parts) ? trim($parts['host'] ?? '', '[]') : '';
        $addresses = $this->addresses($host);
        $public = array_filter($addresses, self::isPublic(...));

        if ($addresses === [] || count($public) !== count($addresses)) {
            $fail('The :attribute must resolve to a public IP address.')->translate();

            return;
        }

        if (! Cidr::isValidAddress($host)) {
            $port = $parts['port'] ?? (strtolower($parts['scheme'] ?? '') === 'https' ? 443 : 80);
            $this->pin = $host.':'.$port.':'.$addresses[0];
        }
    }

    public static function isPublic(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        foreach (self::NON_PUBLIC_BLOCKS as $block) {
            if (Cidr::contains($block, $ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{curl?: array<int, list<string>>}
     */
    public function options(): array
    {
        return $this->pin === null ? [] : ['curl' => [CURLOPT_RESOLVE => [$this->pin]]];
    }

    /**
     * @return list<string>
     */
    private function addresses(string $host): array
    {
        if ($host === '') {
            return [];
        }

        if (Cidr::isValidAddress($host)) {
            return [$host];
        }

        $v4 = gethostbynamel($host) ?: [];
        $v6 = array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6');

        return array_values(array_unique([...$v4, ...$v6]));
    }
}
