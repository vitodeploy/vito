<?php

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Tests\ArchTestCase;

arch('controllers are suffixed with Controller')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch('nothing outside the controllers namespace is suffixed with Controller')
    ->expect('App')
    ->not->toHaveSuffix('Controller')
    ->ignoring('App\Http\Controllers');

arch('controllers extend the base controller')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toExtend(Controller::class)
    ->ignoring(Controller::class);

arch('controllers do not validate — actions do')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\Validator')
    ->ignoring(ArchTestCase::except('http.controllers-validating'));

arch('controllers do not talk to the database directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB')
    ->ignoring(ArchTestCase::except('http.controllers-querying'));

arch('controllers do not dispatch jobs directly — actions own that')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Jobs')
    ->ignoring(ArchTestCase::except('http.controllers-dispatching-jobs'));

arch('form requests are not used — validation lives in actions')
    ->expect('App')
    ->not->toExtend('Illuminate\Foundation\Http\FormRequest');

arch('resources are suffixed with Resource and extend JsonResource')
    ->expect('App\Http\Resources')
    ->classes()
    ->toHaveSuffix('Resource')
    ->toExtend(JsonResource::class);

arch('resources are read models and never mutate state')
    ->expect('App\Http\Resources')
    ->not->toUse([
        'App\Actions',
        'App\Jobs',
        'Illuminate\Support\Facades\DB',
        'Illuminate\Support\Facades\Validator',
    ])
    ->ignoring(ArchTestCase::except('http.resources-calling-actions'));

arch('controllers are only referenced by routing and the http layer itself')
    ->expect('App\Http\Controllers')
    ->toOnlyBeUsedIn(['App\Http', 'App\Providers']);

it('never whitelists fields with parent::toArray()', function (): void {
    $offenders = [];

    foreach (vitoArchFiles('Http/Resources') as $file) {
        if (str_contains((string) file_get_contents($file->getRealPath()), 'parent::toArray')) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
