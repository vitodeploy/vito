<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use phpseclib3\Crypt\RSA;

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

        $privateKey = RSA::createKey(2048);

        File::put($privateKeyPath, $privateKey->toString('PKCS8'));
        chmod($privateKeyPath, 0600);

        File::put($publicKeyPath, $privateKey->getPublicKey()->toString('OpenSSH', ['comment' => 'vito']));
        chmod($publicKeyPath, 0644);

        $this->info('Keys generated successfully.');
    }
}
