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

        $privateKey = escapeshellarg($privateKeyPath);
        $publicKey = escapeshellarg($publicKeyPath);

        exec("openssl genpkey -algorithm RSA -out {$privateKey}", $output, $resultCode);

        if ($resultCode !== 0) {
            $this->error('Unable to generate private key.');

            return;
        }

        chmod($privateKeyPath, 0600);

        exec("ssh-keygen -y -f {$privateKey} > {$publicKey}", $output, $resultCode);

        if ($resultCode !== 0) {
            $this->error('Unable to generate public key.');

            return;
        }

        chmod($publicKeyPath, 0644);

        $this->info('Keys generated successfully.');
    }
}
