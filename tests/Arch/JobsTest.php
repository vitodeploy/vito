<?php

use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Tests\ArchTestCase;

arch('jobs are queued')
    ->expect('App\Jobs')
    ->classes()
    ->toImplement(ShouldQueue::class);

arch('jobs are suffixed with Job')
    ->expect('App\Jobs')
    ->classes()
    ->toHaveSuffix('Job');

arch('jobs expose a handle method')
    ->expect('App\Jobs')
    ->classes()
    ->toHaveMethod('handle');

arch('jobs do not reach into the presentation layer')
    ->expect('App\Jobs')
    ->not->toUse([
        'App\Http\Controllers',
        'Inertia\Inertia',
        'Illuminate\Http\Request',
    ]);

it('uses the Queueable and UniqueQueue traits on every job', function (): void {
    $exempt = ArchTestCase::except('jobs.without-unique-queue');
    $offenders = [];

    foreach (vitoArchClasses('Jobs') as $job) {
        $traits = class_uses_recursive($job);

        if (! in_array(Queueable::class, $traits, true)) {
            $offenders[] = "{$job} is missing Queueable";
        }

        if (! in_array(UniqueQueue::class, $traits, true) && ! in_array($job, $exempt, true)) {
            $offenders[] = "{$job} is missing UniqueQueue";
        }
    }

    expect($offenders)->toBe([]);
});

it('handles its own failure on every job', function (): void {
    $offenders = [];

    foreach (vitoArchClasses('Jobs') as $job) {
        if (! method_exists($job, 'failed')) {
            $offenders[] = $job;
        }
    }

    $exempt = ArchTestCase::except('jobs.without-failure-handler');

    expect(array_values(array_diff($offenders, $exempt)))->toBe([]);
});

it('wraps work in the unique queue lock wherever the trait is used', function (): void {
    $offenders = [];

    foreach (vitoArchFiles('Jobs') as $file) {
        $contents = (string) file_get_contents($file->getRealPath());

        if (! str_contains($contents, 'use UniqueQueue;')) {
            continue;
        }

        if (! str_contains($contents, '$this->run(')) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
