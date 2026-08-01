<?php

use Symfony\Component\Finder\SplFileInfo;
use Tests\ArchTestCase;

it('gives every architecture exception a reason and at least one entry', function (): void {
    $offenders = [];

    foreach (ArchTestCase::EXCEPTIONS as $key => $exception) {
        if (trim($exception['reason']) === '') {
            $offenders[] = "{$key} has no reason";
        }

        if ($exception['entries'] === []) {
            $offenders[] = "{$key} is empty and should be removed";
        }
    }

    expect($offenders)->toBe([]);
});

it('references every architecture exception from a rule', function (): void {
    $rules = '';

    foreach (vitoArchTestFiles() as $file) {
        $rules .= (string) file_get_contents($file->getRealPath());
    }

    $offenders = [];

    foreach (array_keys(ArchTestCase::EXCEPTIONS) as $key) {
        if (! str_contains($rules, "except('{$key}')")) {
            $offenders[] = $key;
        }
    }

    expect($offenders)->toBe([]);
});

it('points every namespaced exception entry at code that still exists', function (): void {
    $offenders = [];

    foreach (ArchTestCase::EXCEPTIONS as $key => $exception) {
        foreach ($exception['entries'] as $entry) {
            if (! str_starts_with($entry, 'App\\')) {
                continue;
            }

            $path = base_path(str_replace(['App\\', '\\'], ['app/', '/'], $entry));

            if (is_dir($path) || is_file($path.'.php')) {
                continue;
            }

            $offenders[] = "{$key}: {$entry}";
        }
    }

    expect($offenders)->toBe([]);
});

it('rejects an unknown architecture exception key', function (): void {
    ArchTestCase::except('does.not.exist');
})->throws(InvalidArgumentException::class);

/**
 * @return array<int, SplFileInfo>
 */
function vitoArchTestFiles(): array
{
    return vitoArchFiles('', __DIR__);
}
