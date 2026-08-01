<?php

use App\Contracts\VitoEnum;
use Illuminate\Support\Str;

arch('everything in the enums namespace is an enum')
    ->expect('App\Enums')
    ->toBeEnums();

arch('enums implement the VitoEnum contract')
    ->expect('App\Enums')
    ->toImplement(VitoEnum::class);

arch('enums stay free of infrastructure dependencies')
    ->expect('App\Enums')
    ->not->toUse([
        'App\Actions',
        'App\Http',
        'App\Jobs',
        'App\Models',
        'Illuminate\Support\Facades\DB',
    ]);

it('backs every enum with a string', function (): void {
    $offenders = [];

    foreach (vitoEnumClasses() as $enum) {
        $reflection = new ReflectionEnum($enum);

        if ((string) $reflection->getBackingType() !== 'string') {
            $offenders[] = $enum;
        }
    }

    expect($offenders)->toBe([]);
});

it('returns a non empty colour and label for every enum case', function (): void {
    $offenders = [];

    foreach (vitoEnumClasses() as $enum) {
        foreach ($enum::cases() as $case) {
            if ($case->getColor() === '' || $case->getText() === '') {
                $offenders[] = "{$enum}::{$case->name}";
            }
        }
    }

    expect($offenders)->toBe([]);
});

/**
 * @return array<int, class-string<VitoEnum&UnitEnum>>
 */
function vitoEnumClasses(): array
{
    $enums = [];

    foreach (vitoArchFiles('Enums') as $file) {
        $enum = 'App\\Enums\\'.Str::of($file->getRelativePathname())
            ->replace(['/', '.php'], ['\\', ''])
            ->toString();

        if (enum_exists($enum)) {
            $enums[] = $enum;
        }
    }

    return $enums;
}
