<?php

use App\Contracts\VitoEnum;
use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Tests\ArchTestCase;

arch('models extend AbstractModel')
    ->expect('App\Models')
    ->classes()
    ->toExtend(AbstractModel::class)
    ->ignoring([
        AbstractModel::class,
        ...ArchTestCase::except('models.foreign-base-class'),
        ...ArchTestCase::except('models.not-on-abstract-model'),
    ]);

arch('eloquent models only live in the models namespace')
    ->expect('App')
    ->not->toExtend(Model::class)
    ->ignoring('App\Models');

arch('models are not suffixed with Model')
    ->expect('App\Models')
    ->classes()
    ->not->toHaveSuffix('Model')
    ->ignoring(AbstractModel::class);

arch('models do not reach into the http layer')
    ->expect('App\Models')
    ->not->toUse([
        'App\Http\Controllers',
        'Inertia\Inertia',
        'Illuminate\Http\Request',
        'Illuminate\Support\Facades\Validator',
    ]);

arch('models do not depend on actions')
    ->expect('App\Models')
    ->not->toUse('App\Actions')
    ->ignoring(ArchTestCase::except('models.calling-actions'));

it('casts every enum attribute to an enum implementing VitoEnum', function (): void {
    $offenders = [];

    foreach (vitoModelClasses() as $model) {
        $instance = new $model;

        foreach ($instance->getCasts() as $attribute => $cast) {
            $cast = Str::before($cast, ':');

            if (! enum_exists($cast)) {
                continue;
            }

            if (! is_subclass_of($cast, VitoEnum::class)) {
                $offenders[] = "{$model}::\${$attribute} casts to {$cast}";
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('encrypts every attribute that stores credentials', function (): void {
    $sensitive = ['credentials', 'secrets', 'token', 'access_token', 'refresh_token', 'password'];
    $offenders = [];

    foreach (vitoModelClasses() as $model) {
        $casts = (new $model)->getCasts();

        foreach ($sensitive as $attribute) {
            if (! array_key_exists($attribute, $casts)) {
                continue;
            }

            if (! Str::startsWith($casts[$attribute], ['encrypted', 'hashed'])) {
                $offenders[] = "{$model}::\${$attribute} is cast as {$casts[$attribute]}";
            }
        }
    }

    expect($offenders)->toBe([]);
});

/**
 * @return array<int, class-string<Model>>
 */
function vitoModelClasses(): array
{
    return array_values(array_filter(
        vitoArchClasses('Models'),
        fn (string $model): bool => is_subclass_of($model, Model::class),
    ));
}
