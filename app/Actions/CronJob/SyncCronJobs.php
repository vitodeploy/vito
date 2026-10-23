<?php

namespace App\Actions\CronJob;

use App\Enums\CronjobStatus;
use App\Exceptions\SSHError;
use App\Models\CronJob;
use App\Models\Server;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncCronJobs
{
    /**
     * Component-owned cron jobs roll back on failure. Site and server cron jobs run their SSH
     * work outside a transaction so the database write lock is not held for the round-trip.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Server $server, Closure $callback, bool $background = false, bool $atomic = false): mixed
    {
        try {
            return Cache::lock('cron-jobs:server-'.$server->id, 600)->block(0, $atomic ? fn () => DB::transaction($callback) : $callback);
        } catch (LockTimeoutException $e) {
            if ($background) {
                throw $e;
            }
            throw ValidationException::withMessages(['name' => 'Another cron job operation is in progress. Please retry.']);
        }
    }

    /**
     * @throws SSHError
     */
    public function sync(Server $server, bool $locked = false): void
    {
        if (! $locked) {
            $this->run($server, fn () => $this->sync($server, locked: true));

            return;
        }
        $users = $server->getSshUsers();

        foreach ($users as $user) {
            $this->syncUserCronJobs($server, $user);
        }
    }

    /**
     * @throws SSHError
     */
    private function syncUserCronJobs(Server $server, string $user): void
    {
        // Get existing cronjobs from server for this user
        $crontabOutput = $this->getUserCrontab($server, $user);

        // Get all Vito-managed cronjobs for this user (including both server-level and site-level)
        $vitoCronJobs = $server->cronJobs()->whereNull('component_id')
            ->where('user', $user)
            ->get();

        $serverLevelCronJobs = $vitoCronJobs->where('site_id', null)->where('component_id', null);

        if (empty($crontabOutput)) {
            // If crontab is empty, mark all server-level Vito cronjobs as disabled
            foreach ($serverLevelCronJobs as $cronJob) {
                if ($cronJob->status === CronjobStatus::READY) {
                    $cronJob->update(['status' => CronjobStatus::DISABLED]);
                }
            }

            return;
        }

        $lines = explode("\n", trim($crontabOutput));
        $serverCronJobs = [];
        $foundCronJobs = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            $isCommented = str_starts_with($line, '#');

            // If it's a comment, remove the # and try to parse it
            if ($isCommented) {
                $line = ltrim($line, '#');
                $line = trim($line);
            }

            // Parse cron line: frequency command
            $parts = preg_split('/\s+/', $line, 6);
            if (count($parts) < 6) {
                continue;
            }

            // Validate that the first 5 parts look like cron time fields
            // Valid cron fields contain: numbers, *, -, /, and ,
            $isValidCronFormat = true;
            for ($i = 0; $i < 5; $i++) {
                if (! preg_match('/^[\d\*\-\/,]+$/', $parts[$i])) {
                    $isValidCronFormat = false;
                    break;
                }
            }

            if (! $isValidCronFormat) {
                continue;
            }

            $frequency = $this->normalizeFrequency(implode(' ', array_slice($parts, 0, 5)));
            $command = $this->normalizeCommand($parts[5]);

            if (preg_match('/ # vito-component-cron:\d+$/', $command)) {
                continue;
            }

            $serverCronJobs[] = [
                'frequency' => $frequency,
                'command' => $command,
                'commented' => $isCommented,
            ];

            // Check if this matches any Vito-managed cronjob (including site-level ones)
            $matchingCronJob = $vitoCronJobs->first(function ($cronJob) use ($frequency, $command) {
                return $this->normalizeFrequency($cronJob->frequency) === $frequency && $this->matchesCommand($cronJob->command, $command);
            });

            if ($matchingCronJob) {
                $foundCronJobs[] = $matchingCronJob->id;

                // Update status based on comment state (only for server-level cronjobs)
                if ($matchingCronJob->site_id === null && $matchingCronJob->component_id === null) {
                    if ($isCommented && $matchingCronJob->status === CronjobStatus::READY) {
                        $matchingCronJob->update(['status' => CronjobStatus::DISABLED]);
                    } elseif (! $isCommented && $matchingCronJob->status === CronjobStatus::DISABLED) {
                        $matchingCronJob->update(['status' => CronjobStatus::READY]);
                    }
                }
            }
        }

        // Mark server-level Vito cronjobs that are no longer on the server as disabled
        foreach ($serverLevelCronJobs as $cronJob) {
            if (! in_array($cronJob->id, $foundCronJobs) && $cronJob->status === CronjobStatus::READY) {
                $cronJob->update(['status' => CronjobStatus::DISABLED]);
            }
        }

        // Create new cronjobs for manually created ones (not in Vito)
        foreach ($serverCronJobs as $cronJobData) {
            $isVitoManaged = $vitoCronJobs->contains(function ($cronJob) use ($cronJobData) {
                return $this->normalizeFrequency($cronJob->frequency) === $cronJobData['frequency'] && $this->matchesCommand($cronJob->command, $cronJobData['command']);
            });

            if (! $isVitoManaged) {
                $server->cronJobs()->create([
                    'site_id' => null, // Server-level cronjob
                    'user' => $user,
                    'command' => $cronJobData['command'],
                    'frequency' => $cronJobData['frequency'],
                    'status' => $cronJobData['commented'] ? CronjobStatus::DISABLED : CronjobStatus::READY,
                ]);
            }
        }
    }

    private function normalizeFrequency(string $frequency): string
    {
        // Normalize frequency by ensuring single spaces between parts
        return preg_replace('/\s+/', ' ', trim($frequency));
    }

    private function normalizeCommand(string $command): string
    {
        // Normalize command by ensuring single spaces between parts
        return preg_replace('/\s+/', ' ', trim($command));
    }

    private function matchesCommand(string $cronJobCommand, string $serverCommand): bool
    {
        $normalizedCronJob = $this->normalizeCommand($cronJobCommand);
        if ($normalizedCronJob === $serverCommand) {
            return true;
        }

        $unwrapped = CronJob::unwrapCommand($serverCommand);

        return $unwrapped !== null && $this->normalizeCommand($unwrapped) === $normalizedCronJob;
    }

    /**
     * @throws SSHError
     */
    private function getUserCrontab(Server $server, string $user): string
    {
        $output = $server->ssh($user)->exec("crontab -l 2>/dev/null || echo ''", 'get-user-crontab');

        // Remove the "cron updated!" message that might be at the end
        $output = str_replace('cron updated!', '', $output);

        return trim($output);
    }
}
