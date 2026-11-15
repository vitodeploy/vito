<?php

namespace App\Helpers;

class Apr1Hasher
{
    private const ALPHABET = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public static function hash(string $password, ?string $salt = null): string
    {
        $salt = self::sanitiseSalt($salt ?? self::randomSalt());

        $bin = pack('H*', md5($password.$salt.$password));

        $text = $password.'$apr1$'.$salt;
        $passwordLen = strlen($password);
        for ($i = $passwordLen; $i > 0; $i -= 16) {
            $text .= substr($bin, 0, min(16, $i));
        }
        for ($i = $passwordLen; $i > 0; $i >>= 1) {
            $text .= ($i & 1) ? "\0" : $password[0];
        }
        $bin = pack('H*', md5($text));

        for ($n = 0; $n < 1000; $n++) {
            $tmp = ($n & 1) ? $password : $bin;
            if ($n % 3) {
                $tmp .= $salt;
            }
            if ($n % 7) {
                $tmp .= $password;
            }
            $tmp .= ($n & 1) ? $bin : $password;
            $bin = pack('H*', md5($tmp));
        }

        return '$apr1$'.$salt.'$'.self::encode($bin);
    }

    private static function randomSalt(): string
    {
        return substr(str_replace(['+', '/', '='], '.', base64_encode(random_bytes(6))), 0, 8);
    }

    private static function sanitiseSalt(string $salt): string
    {
        $salt = preg_replace('/[^A-Za-z0-9.\/]/', '.', $salt) ?? '';

        return substr(str_pad($salt, 8, '.'), 0, 8);
    }

    private static function encode(string $bin): string
    {
        $out = '';
        $triplets = [[0, 6, 12], [1, 7, 13], [2, 8, 14], [3, 9, 15], [4, 10, 5]];

        foreach ($triplets as [$a, $b, $c]) {
            $v = (ord($bin[$a]) << 16) | (ord($bin[$b]) << 8) | ord($bin[$c]);
            for ($i = 0; $i < 4; $i++) {
                $out .= self::ALPHABET[$v & 0x3F];
                $v >>= 6;
            }
        }

        $v = ord($bin[11]);
        for ($i = 0; $i < 2; $i++) {
            $out .= self::ALPHABET[$v & 0x3F];
            $v >>= 6;
        }

        return $out;
    }
}
