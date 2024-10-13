<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateKeysCommand extends Command
{
    protected $signature = 'ssh-key:generate {--force}';

    protected $description = 'Generate keys';

    public function handle(): void
    {
        $privateKeyPath = storage_path('ssh-private.pem');
        $publicKeyPath = storage_path('ssh-public.key');

        if (File::exists($privateKeyPath) && File::exists($publicKeyPath) && ! $this->option('force')) {
            $this->error('Keys already exist. Use --force to overwrite.');

            return;
        }

        exec('openssl genpkey -algorithm RSA -out '.$privateKeyPath);
        exec('chmod 600 '.$privateKeyPath);
        exec('ssh-keygen -y -f '.$privateKeyPath.' > '.$publicKeyPath);
        exec('chown -R '.get_current_user().':'.get_current_user().' '.$privateKeyPath);
        exec('chown -R '.get_current_user().':'.get_current_user().' '.$publicKeyPath);

        $this->info('Keys generated successfully.');
    }
}
