<?php

use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function vitoPestFeatureUniqueQueueTestMakeJob(callable $callback): object
{
    return new class($callback)
    {
        use Queueable;
        use UniqueQueue;

        /** @var callable */
        private $callback;

        public function __construct(callable $callback)
        {
            $this->callback = $callback;
        }

        public function execute(): void
        {
            $this->run('unique-queue-test', $this->callback);
        }
    };
}

test('transient database lock is released for retry', function () {
    $job = vitoPestFeatureUniqueQueueTestMakeJob(fn () => throw new RuntimeException('SQLSTATE[HY000]: General error: 5 database is locked'));

    $queueJob = Mockery::mock(JobContract::class);
    $queueJob->shouldReceive('attempts')->andReturn(1);
    $queueJob->shouldReceive('release')->once();
    $queueJob->shouldReceive('fail')->never();
    $job->setJob($queueJob);

    $job->execute();
});

test('non transient error fails the job', function () {
    $job = vitoPestFeatureUniqueQueueTestMakeJob(fn () => throw new RuntimeException('something else broke'));

    $queueJob = Mockery::mock(JobContract::class);
    $queueJob->shouldReceive('attempts')->andReturn(1);
    $queueJob->shouldReceive('fail')->once();
    $queueJob->shouldReceive('release')->never();
    $job->setJob($queueJob);

    $job->execute();
});

test('nested cache lock contention releases the job and its outer lock', function () {
    $job = vitoPestFeatureUniqueQueueTestMakeJob(fn () => throw new \Illuminate\Contracts\Cache\LockTimeoutException);
    $queueJob = Mockery::mock(JobContract::class);
    $queueJob->shouldReceive('release')->once()->with(30);
    $queueJob->shouldNotReceive('fail');
    $job->setJob($queueJob);

    $job->execute();

    expect(\Illuminate\Support\Facades\Cache::lock('unique-queue:unique-queue-test', 60)->get(fn () => true))->toBeTrue();
});
