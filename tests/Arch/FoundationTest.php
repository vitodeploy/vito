<?php

use Illuminate\Support\Str;
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
 * Every type declared under app/ — classes, interfaces, enums and traits, at any
 * nesting depth — so a method is checked in the file that declares it.
 *
 * @return array<int, class-string>
 */
function vitoArchTypes(): array
{
    $types = [];

    foreach (vitoArchFiles() as $file) {
        if (preg_match('/^(?:final |abstract |readonly )*(?:class|interface|enum|trait) /m', $file->getContents()) !== 1) {
            continue;
        }

        $type = Str::of($file->getRealPath())
            ->after(app_path().DIRECTORY_SEPARATOR)
            ->replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''])
            ->prepend('App\\')
            ->toString();

        if (class_exists($type) || interface_exists($type) || enum_exists($type) || trait_exists($type)) {
            $types[] = $type;
        }
    }

    return $types;
}
