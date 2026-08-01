<?php

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Tokens that mean a migration is doing more than schema and data work.
 *
 * @var array<string, string>
 */
const FORBIDDEN_MIGRATION_TOKENS = [
    'dispatch(' => 'dispatches a job',
    'App\Jobs' => 'references a job',
    'SSH::' => 'opens an SSH connection',
    'App\Facades\SSH' => 'opens an SSH connection',
    'Http::' => 'makes an HTTP call',
    'Bus::' => 'touches the bus',
    'Queue::' => 'touches the queue',
    'SocketEvent::' => 'broadcasts an event',
    'broadcast(' => 'broadcasts an event',
    'Notification::' => 'sends a notification',
    'Mail::' => 'sends mail',
];

it('keeps migrations free of runtime side effects', function (): void {
    $offenders = [];

    foreach (migrationFiles() as $file) {
        $contents = (string) file_get_contents($file->getRealPath());

        foreach (FORBIDDEN_MIGRATION_TOKENS as $token => $reason) {
            if (str_contains($contents, $token)) {
                $offenders[] = "{$file->getFilename()} {$reason}";
            }
        }
    }

    expect(array_values(array_unique($offenders)))->toBe([]);
});

/**
 * @return array<int, SplFileInfo>
 */
function migrationFiles(): array
{
    return iterator_to_array(
        Finder::create()->files()->in(database_path('migrations'))->name('*.php'),
        false
    );
}
