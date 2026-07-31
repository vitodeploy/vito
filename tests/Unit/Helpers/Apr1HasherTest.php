<?php

use App\Helpers\Apr1Hasher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->knownVectors = [
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
});

test('hash produces apr1 format', function () {
    $hash = Apr1Hasher::hash('password');

    expect($hash)->toMatch('/^\$apr1\$[A-Za-z0-9.\/]{8}\$[A-Za-z0-9.\/]{22}$/');
});

test('hash is deterministic for same salt', function () {
    $hash1 = Apr1Hasher::hash('password', 'salt1234');
    $hash2 = Apr1Hasher::hash('password', 'salt1234');

    expect($hash2)->toBe($hash1);
});

test('hash differs with different salts', function () {
    $hash1 = Apr1Hasher::hash('password', 'salt1234');
    $hash2 = Apr1Hasher::hash('password', 'differen');

    $this->assertNotSame($hash1, $hash2);
});

test('hash differs with different passwords', function () {
    $hash1 = Apr1Hasher::hash('password1', 'salt1234');
    $hash2 = Apr1Hasher::hash('password2', 'salt1234');

    $this->assertNotSame($hash1, $hash2);
});

test('random salt is used when not provided', function () {
    $hash1 = Apr1Hasher::hash('password');
    $hash2 = Apr1Hasher::hash('password');

    $this->assertNotSame($hash1, $hash2);
});

test('hash against known vectors', function () {
    foreach ($this->knownVectors as $name => $vector) {
        expect(Apr1Hasher::hash($vector['password'], $vector['salt']))->toBe($vector['expected'], "Vector '{$name}' failed");
    }
});

test('salt is sanitised when containing invalid characters', function () {
    $hash = Apr1Hasher::hash('password', 'bad!@#$%');

    expect($hash)->toMatch('/^\$apr1\$[A-Za-z0-9.\/]{8}\$[A-Za-z0-9.\/]{22}$/');
});

test('short salt is padded', function () {
    $hash = Apr1Hasher::hash('password', 'ab');

    preg_match('/^\$apr1\$([^$]+)\$/', $hash, $matches);
    expect(strlen($matches[1]))->toBe(8);
});

test('long salt is truncated', function () {
    $hash = Apr1Hasher::hash('password', 'abcdefghijklmnop');

    preg_match('/^\$apr1\$([^$]+)\$/', $hash, $matches);
    expect(strlen($matches[1]))->toBe(8);
    expect($matches[1])->toBe('abcdefgh');
});
