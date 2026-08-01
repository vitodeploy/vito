<?php

use Illuminate\Support\Str;
use Tests\ArchTestCase;

/**
 * Layers that answer questions or shape output. Nothing here may reach a
 * server or otherwise cause a side effect.
 *
 * Each source namespace needs its own expectation — Pest's negative dependency
 * expectations silently pass when given an array of source namespaces.
 */
const READ_AND_DECISION_LAYERS = [
    'App\DTOs',
    'App\Enums',
    'App\Http\Resources',
    'App\Policies',
];

arch('remote commands never shell out')
    ->expect([
        'Symfony\Component\Process\Process',
        'Illuminate\Support\Facades\Process',
        'proc_open',
        'popen',
    ])
    ->not->toBeUsed()
    ->ignoring(ArchTestCase::except('security.local-process'));

arch('the ssh facade is never reached from a read or decision layer')
    ->expect('App\Facades\SSH')
    ->not->toBeUsedIn(READ_AND_DECISION_LAYERS);

arch('the ssh helper is never reached from a read or decision layer')
    ->expect('App\Helpers\SSH')
    ->not->toBeUsedIn(READ_AND_DECISION_LAYERS);

it('never logs a credential-shaped variable', function (): void {
    $sensitive = ['password', 'token', 'secret', 'private_key', 'credentials', 'api_key', 'pk'];
    $offenders = [];

    foreach (vitoArchFiles() as $file) {
        foreach (file($file->getRealPath()) ?: [] as $number => $line) {
            if (! Str::contains($line, ['Log::', 'logger(', 'info(', 'error(', 'warning(', 'debug('])) {
                continue;
            }

            foreach ($sensitive as $needle) {
                if (Str::contains($line, ['$'.$needle, "'".$needle."'", '->'.$needle])) {
                    $offenders[] = "{$file->getRelativePathname()}:".($number + 1);
                }
            }
        }
    }

    expect(array_values(array_unique($offenders)))->toBe([]);
});

it('does not expose filesystem paths of keys and certificates through resources', function (): void {
    $offenders = [];

    foreach (vitoArchFiles('Http/Resources') as $file) {
        $contents = (string) file_get_contents($file->getRealPath());

        preg_match_all("/'(?<key>[a-z_]+_path)' =>/", $contents, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $offenders[] = "{$file->getRelativePathname()}: {$match['key']}";
        }
    }

    expect($offenders)->toBe([]);
});
