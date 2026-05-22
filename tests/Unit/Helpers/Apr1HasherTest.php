<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Apr1Hasher;
use Tests\TestCase;

class Apr1HasherTest extends TestCase
{
    /**
     * Known-good vectors verified against Apache's htpasswd -vb.
     *
     * @var array<string, array{password: string, salt: string, expected: string}>
     */
    private array $knownVectors = [
        'simple' => [
            'password' => 'password',
            'salt' => 'salt1234',
            'expected' => '$apr1$salt1234$k3J5yKYW6TlGmTytnkXbQ0',
        ],
        'short_password' => [
            'password' => 'a',
            'salt' => 'abcdefgh',
            'expected' => '$apr1$abcdefgh$G8IsPsylW5ROvIKsQMRG61',
        ],
        'long_password' => [
            'password' => 'this-is-a-much-longer-password-with-many-characters',
            'salt' => 'longsalt',
            'expected' => '$apr1$longsalt$H18n0FnZ3fZTIJggqWyvU/',
        ],
        'special_chars' => [
            'password' => 'p@ssw0rd!#$%',
            'salt' => 'specials',
            'expected' => '$apr1$specials$gvYEtzxoUQMAV.udPg/lH1',
        ],
        'empty_password' => [
            'password' => '',
            'salt' => 'salt1234',
            'expected' => '$apr1$salt1234$5H34QiH40LY95k4Ru.Rsw0',
        ],
    ];

    public function test_hash_produces_apr1_format(): void
    {
        $hash = Apr1Hasher::hash('password');

        $this->assertMatchesRegularExpression(
            '/^\$apr1\$[A-Za-z0-9.\/]{8}\$[A-Za-z0-9.\/]{22}$/',
            $hash
        );
    }

    public function test_hash_is_deterministic_for_same_salt(): void
    {
        $hash1 = Apr1Hasher::hash('password', 'salt1234');
        $hash2 = Apr1Hasher::hash('password', 'salt1234');

        $this->assertSame($hash1, $hash2);
    }

    public function test_hash_differs_with_different_salts(): void
    {
        $hash1 = Apr1Hasher::hash('password', 'salt1234');
        $hash2 = Apr1Hasher::hash('password', 'differen');

        $this->assertNotSame($hash1, $hash2);
    }

    public function test_hash_differs_with_different_passwords(): void
    {
        $hash1 = Apr1Hasher::hash('password1', 'salt1234');
        $hash2 = Apr1Hasher::hash('password2', 'salt1234');

        $this->assertNotSame($hash1, $hash2);
    }

    public function test_random_salt_is_used_when_not_provided(): void
    {
        $hash1 = Apr1Hasher::hash('password');
        $hash2 = Apr1Hasher::hash('password');

        $this->assertNotSame($hash1, $hash2);
    }

    public function test_hash_against_known_vectors(): void
    {
        foreach ($this->knownVectors as $name => $vector) {
            $this->assertSame(
                $vector['expected'],
                Apr1Hasher::hash($vector['password'], $vector['salt']),
                "Vector '{$name}' failed"
            );
        }
    }

    public function test_salt_is_sanitised_when_containing_invalid_characters(): void
    {
        $hash = Apr1Hasher::hash('password', 'bad!@#$%');

        $this->assertMatchesRegularExpression(
            '/^\$apr1\$[A-Za-z0-9.\/]{8}\$[A-Za-z0-9.\/]{22}$/',
            $hash
        );
    }

    public function test_short_salt_is_padded(): void
    {
        $hash = Apr1Hasher::hash('password', 'ab');

        preg_match('/^\$apr1\$([^$]+)\$/', $hash, $matches);
        $this->assertSame(8, strlen($matches[1]));
    }

    public function test_long_salt_is_truncated(): void
    {
        $hash = Apr1Hasher::hash('password', 'abcdefghijklmnop');

        preg_match('/^\$apr1\$([^$]+)\$/', $hash, $matches);
        $this->assertSame(8, strlen($matches[1]));
        $this->assertSame('abcdefgh', $matches[1]);
    }
}
