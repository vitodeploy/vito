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

    protected function lockSeconds(): int
    {
        return 600;
    }

    public function run(string $key, Closure $callback): void
    {
        $lockKey = "unique-queue:$key";

        $lock = Cache::lock($lockKey, $this->lockSeconds());

        if ($lock->get()) {
            try {
                $callback();
            } catch (Throwable $e) {
                if ($this->isTransientDatabaseError($e) && $this->attempts() < $this->tries) {
                    $this->release(min(30, $this->attempts() * 2));

                    return;
                }

                $this->fail($e);
            } finally {
                $lock->release();
            }
        } else {
            $this->release(30);
        }
    }

    protected function isTransientDatabaseError(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        foreach (['database is locked', 'deadlock', 'lock wait timeout', 'try restarting transaction'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
