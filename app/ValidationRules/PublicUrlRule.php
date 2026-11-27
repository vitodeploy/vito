<?php

namespace App\ValidationRules;

use App\Support\Cidr;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects URLs whose host resolves to a loopback, private, link-local or reserved address.
 * After validation, `options()` pins the request to the checked address and bypasses any
 * configured proxy, so neither a second DNS lookup nor a proxy can reach an internal host.
 */
class PublicUrlRule implements ValidationRule
{
    private const NON_PUBLIC_BLOCKS = [
        '100.64.0.0/10',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '::/96',
        '::ffff:0:0/96',
        '::ffff:0:0:0/96',
        '64:ff9b::/96',
        '64:ff9b:1::/48',
        '2001::/32',
        '2001:db8::/32',
        '2002::/16',
        'fec0::/10',
    ];

    private ?string $pin = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $this->pin = null;
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
            $this->pin = $host.':'.$port.':'.implode(',', array_map(fn (string $ip): string => Cidr::isV6($ip) ? '['.$ip.']' : $ip, $addresses));
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
     * @return array{proxy: array{no: list<string>}, curl?: array<int, list<string>>}
     */
    public function options(): array
    {
        $options = ['proxy' => ['no' => ['*']]];

        if ($this->pin !== null) {
            $options['curl'] = [CURLOPT_RESOLVE => [$this->pin]];
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    protected function addresses(string $host): array
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
