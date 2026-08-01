<?php

use Tests\ArchTestCase;

arch('php best practices are followed')
    ->preset()
    ->php()
    ->ignoring(ArchTestCase::except('foundation.var-export'));

arch('security sensitive functions are not used')
    ->preset()
    ->security()
    ->ignoring([
        ...ArchTestCase::except('foundation.non-cryptographic-hashing'),
        ...ArchTestCase::except('foundation.local-shell'),
        ...ArchTestCase::except('foundation.assert'),
    ]);

arch('laravel debugging helpers never reach the codebase')
    ->expect(['dd', 'ddd', 'exit'])
    ->not->toBeUsed();

arch('env() is only read from config files')
    ->expect('env')
    ->not->toBeUsed();

arch('sleeping is done through the Sleep facade so tests can fake it')
    ->expect(['sleep', 'usleep'])
    ->not->toBeUsed()
    ->ignoring(ArchTestCase::except('foundation.blocking-sleep'));

arch('traits live in the traits namespace')
    ->expect('App\Traits')
    ->toBeTraits();

arch('contracts are interfaces')
    ->expect('App\Contracts')
    ->toBeInterfaces();

arch('enums only live in the enums namespace')
    ->expect('App')
    ->not->toBeEnums()
    ->ignoring('App\Enums');

arch('exceptions are throwable and live in the exceptions namespace')
    ->expect('App\Exceptions')
    ->classes()
    ->toImplement(Throwable::class)
    ->ignoring(ArchTestCase::except('foundation.non-throwable-in-exceptions'));

arch('nothing outside the exceptions namespace is throwable')
    ->expect('App')
    ->not->toImplement(Throwable::class)
    ->ignoring('App\Exceptions');

it('declares an explicit return type on every method', function (): void {
    $exempt = ArchTestCase::except('foundation.missing-return-type');
    $offenders = [];

    foreach (vitoArchTypes() as $class) {
        if (in_array($class, $exempt, true)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        foreach ($reflection->getMethods() as $method) {
            if ($method->getFileName() !== $reflection->getFileName() || $method->isConstructor()) {
                continue;
            }

            if (! $method->hasReturnType()) {
                $offenders[] = "{$class}::{$method->getName()}()";
            }
        }
    }

    expect($offenders)->toBe([]);
});

/**
 * Every concrete class plus every app-owned trait, so methods flattened from
 * App\Traits are checked in the file that declares them.
 *
 * @return array<int, class-string>
 */
function vitoArchTypes(): array
{
    $types = vitoArchClasses();

    foreach (vitoArchFiles('Traits') as $file) {
        $trait = 'App\\Traits\\'.str_replace('.php', '', $file->getRelativePathname());

        if (trait_exists($trait)) {
            $types[] = $trait;
        }
    }

    return $types;
}
