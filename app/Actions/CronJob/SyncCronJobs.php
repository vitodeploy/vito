<?php

namespace App\Actions\CronJob;

use App\Enums\CronjobStatus;
use App\Exceptions\SSHError;
use App\Models\CronJob;
use App\Models\Server;

class SyncCronJobs
{
    /**
     * @throws SSHError
     */
    public function sync(Server $server): void
    {
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
        $vitoCronJobs = $server->cronJobs()
            ->where('user', $user)
            ->get();

        // Filter only server-level cronjobs (site_id = null) for status updates
        $serverLevelCronJobs = $vitoCronJobs->where('site_id', null);

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

            $serverCronJobs[] = [
                'frequency' => $frequency,
                'command' => $command,
                'commented' => $isCommented,
            ];

            // Check if this matches any Vito-managed cronjob (including site-level ones)
            $matchingCronJob = $vitoCronJobs->first(function ($cronJob) use ($frequency, $command) {
                return $this->normalizeFrequency($cronJob->frequency) === $frequency && $this->normalizeCommand($cronJob->command) === $command;
            });

            if ($matchingCronJob) {
                $foundCronJobs[] = $matchingCronJob->id;

                // Update status based on comment state (only for server-level cronjobs)
                if ($matchingCronJob->site_id === null) {
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
                return $this->normalizeFrequency($cronJob->frequency) === $cronJobData['frequency'] && $this->normalizeCommand($cronJob->command) === $cronJobData['command'];
            });

            if (! $isVitoManaged) {
                $server->cronJobs()->create([
                    'site_id' => null, // Server-level cronjob
                    'user' => $user,
                    'command' => $cronJobData['command'],
                    'frequency' => $cronJobData['frequency'],
                    'hidden' => false,
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
