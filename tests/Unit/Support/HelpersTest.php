<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;

uses(RefreshDatabase::class);

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

    $path = sys_get_temp_dir().'/vito-missing-'.uniqid().'/key';

    try {
        generate_key_pair($path);
        $this->fail('Expected generate_key_pair() to throw.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())
            ->toBe('Failed to generate SSH key pair.')
            ->not->toContain($path);
    }
});

it('tails a file without reading more than the byte budget', function (): void {
    file_put_contents($this->keyPath, "first\nsecond\nthird\n");

    expect(tail($this->keyPath, 2))->toBe("second\nthird")
        ->and(tail($this->keyPath, 10))->toBe("first\nsecond\nthird")
        ->and(tail($this->keyPath.'.missing', 2))->toBe('');

    $handle = fopen($this->keyPath, 'wb');
    ftruncate($handle, 64 * 1024 * 1024);
    fseek($handle, 0, SEEK_END);
    fwrite($handle, 'end');
    fclose($handle);

    $before = memory_get_peak_usage();
    expect(strlen(tail($this->keyPath, 50, 4096)))->toBeLessThanOrEqual(4096)
        ->and(tail($this->keyPath, 50, 4096))->toEndWith('end')
        ->and(memory_get_peak_usage() - $before)->toBeLessThan(1024 * 1024);
});

it('substitutes variables in one pass so inserted values stay literal', function (): void {
    expect(substitute_variables('${A}-${B}', ['A' => '${B}${B}', 'B' => 'x']))->toBe('${B}${B}-x')
        ->and(substitute_variables('${A}', ['A' => '', 'B' => '0']))->toBe('${A}')
        ->and(fn () => substitute_variables(str_repeat('${A}', 5000), ['A' => str_repeat('a', 255)]))
        ->toThrow(ValidationException::class, 'too large')
        ->and(strlen(substitute_variables(str_repeat('${A}', 5000), ['A' => str_repeat('a', 206)])))->toBe(1030000)
        ->and(substitute_variables('${LONG_NAME}', ['LONG_NAME' => 'x']))->toBe('x');
});

it('caps webserver config indentation so deep nesting stays linear', function (): void {
    $formatted = format_webserver_config(str_repeat("a {\n", 5000).str_repeat("}\n", 5000));

    expect(strlen($formatted))->toBeLessThan(5000 * 2 * (4 * 32 + 4))
        ->and(format_webserver_config("server {\nlisten 80;\n}"))->toBe("server {\n    listen 80;\n}\n");
});

it('normalizes absolute paths without traversing above root', function (string $path, string $expected): void {
    expect(absolute_path($path))->toBe($expected);
})->with([
    ['/var//www/./app/../logs/', '/var/www/logs'],
    ['/../../var/../tmp', '/tmp'],
    ['var/www/../logs', '/var/logs'],
    ['', '/'],
]);
