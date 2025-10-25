<?php

namespace App\Traits;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

trait UniqueQueue
{
    public $tries = 120;

    public function retryUntil(): \DateTime
    {
        return now()->addHour();
    }

    public function run(string $key, Closure $callback): void
    {
        $lockKey = "unique-queue:$key";

        $lock = Cache::lock($lockKey, 600);

        if ($lock->get()) {
            try {
                $callback();
            } catch (Throwable $e) {
                $lock->release();
                $this->fail($e);
            } finally {
                $lock->release();
            }
        } else {
            $this->release(30);
        }
    }
}
