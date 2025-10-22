<?php

namespace App\Traits;

use Closure;
use Illuminate\Support\Facades\Cache;

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
            } finally {
                $lock->release();
            }
        } else {
            $this->release(30);
        }

    }
}
