<?php

use App\Models\User;
use App\Traits\HasRolePolicies;
use Tests\ArchTestCase;

arch('policies are suffixed with Policy')
    ->expect('App\Policies')
    ->classes()
    ->toHaveSuffix('Policy');

arch('nothing outside the policies namespace is suffixed with Policy')
    ->expect('App')
    ->not->toHaveSuffix('Policy')
    ->ignoring('App\Policies');

arch('policies decide, they do not act')
    ->expect('App\Policies')
    ->not->toUse([
        'App\Http\Controllers',
        'App\Jobs',
        'Illuminate\Support\Facades\DB',
        'Illuminate\Support\Facades\Validator',
        'Inertia\Inertia',
    ]);

arch('policies are resolved through the gate, never called directly')
    ->expect('App\Policies')
    ->toOnlyBeUsedIn(['App\Policies', 'App\Providers']);

it('checks project roles through HasRolePolicies', function (): void {
    $offenders = [];

    foreach (vitoArchClasses('Policies') as $policy) {
        if (in_array(HasRolePolicies::class, class_uses_recursive($policy), true)) {
            continue;
        }

        $offenders[] = $policy;
    }

    $exempt = ArchTestCase::except('policies.user-owned');

    expect(array_values(array_diff($offenders, $exempt)))->toBe([]);
});

it('takes a User as the first argument of every policy method and returns bool', function (): void {
    $offenders = [];

    foreach (vitoArchClasses('Policies') as $policy) {
        $reflection = new ReflectionClass($policy);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getFileName() !== $reflection->getFileName()) {
                continue;
            }

            $parameters = $method->getParameters();
            $returnType = $method->getReturnType();

            if ($parameters === [] || (string) $parameters[0]->getType() !== User::class) {
                $offenders[] = "{$policy}::{$method->getName()}() does not take a User first";
            }

            if (! $returnType instanceof ReflectionNamedType || $returnType->getName() !== 'bool') {
                $offenders[] = "{$policy}::{$method->getName()}() does not return bool";
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('maps every policy to an existing model', function (): void {
    $offenders = [];

    foreach (vitoArchClasses('Policies') as $policy) {
        $model = str_replace(['App\\Policies\\', 'Policy'], ['App\\Models\\', ''], $policy);

        if (! class_exists($model)) {
            $offenders[] = "{$policy} has no matching model";
        }
    }

    expect($offenders)->toBe([]);
});
