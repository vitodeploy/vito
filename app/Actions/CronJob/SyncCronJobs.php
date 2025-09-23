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

        // Get all Vito-managed cronjobs for this user
        $vitoCronJobs = $server->cronJobs()
            ->where('user', $user)
            ->where('site_id', null) // Only server-level cronjobs
            ->get();

        if (empty($crontabOutput)) {
            // If crontab is empty, mark all Vito cronjobs as disabled
            foreach ($vitoCronJobs as $cronJob) {
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

            $frequency = implode(' ', array_slice($parts, 0, 5));
            $command = $parts[5];

            $serverCronJobs[] = [
                'frequency' => $frequency,
                'command' => $command,
                'commented' => $isCommented,
            ];

            // Check if this matches a Vito-managed cronjob
            $matchingCronJob = $vitoCronJobs->first(function ($cronJob) use ($frequency, $command) {
                return $cronJob->frequency === $frequency && $cronJob->command === $command;
            });

            if ($matchingCronJob) {
                $foundCronJobs[] = $matchingCronJob->id;

                // Update status based on comment state
                if ($isCommented && $matchingCronJob->status === CronjobStatus::READY) {
                    $matchingCronJob->update(['status' => CronjobStatus::DISABLED]);
                } elseif (! $isCommented && $matchingCronJob->status === CronjobStatus::DISABLED) {
                    $matchingCronJob->update(['status' => CronjobStatus::READY]);
                }
            }
        }

        // Mark Vito cronjobs that are no longer on the server as disabled
        foreach ($vitoCronJobs as $cronJob) {
            if (! in_array($cronJob->id, $foundCronJobs) && $cronJob->status === CronjobStatus::READY) {
                $cronJob->update(['status' => CronjobStatus::DISABLED]);
            }
        }

        // Create new cronjobs for manually created ones (not in Vito)
        foreach ($serverCronJobs as $cronJobData) {
            $isVitoManaged = $vitoCronJobs->contains(function ($cronJob) use ($cronJobData) {
                return $cronJob->frequency === $cronJobData['frequency'] && $cronJob->command === $cronJobData['command'];
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
