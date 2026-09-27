<?php

use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;

beforeEach(function (): void {
    $this->keyPath = sys_get_temp_dir().'/vito-test-key-'.uniqid();
});

afterEach(function (): void {
    foreach ([$this->keyPath, $this->keyPath.'.pub'] as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }
});

it('generates a key pair the ssh helper can load', function (): void {
    generate_key_pair($this->keyPath);

    expect($this->keyPath)->toBeReadableFile()
        ->and($this->keyPath.'.pub')->toBeReadableFile()
        ->and(fileperms($this->keyPath) & 0777)->toBe(0400)
        ->and(PublicKeyLoader::loadPrivateKey((string) file_get_contents($this->keyPath)))
        ->toBeInstanceOf(PrivateKey::class);
});

it('fails loudly without leaking the key path when generation fails', function (): void {
    Log::shouldReceive('error')->once();

    $path = '/this-directory-does-not-exist/key';

    try {
        generate_key_pair($path);
        $this->fail('Expected generate_key_pair() to throw.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())
            ->toBe('Failed to generate SSH key pair.')
            ->not->toContain($path);
    }
});
