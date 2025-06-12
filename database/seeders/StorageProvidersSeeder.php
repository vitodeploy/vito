<?php

namespace Database\Seeders;

use App\Models\StorageProvider;
use App\StorageProviders\FTP;
use App\StorageProviders\S3;
use Illuminate\Database\Seeder;

class StorageProvidersSeeder extends Seeder
{
    public function run(): void
    {
        StorageProvider::factory()->create([
            'profile' => 'FTP',
            'provider' => FTP::id(),
            'credentials' => [
                'host' => 'ftp.example.com',
                'username' => 'ftp_user',
                'password' => 'ftp_password',
            ],
        ]);

        StorageProvider::factory()->create([
            'profile' => 'S3',
            'provider' => S3::id(),
            'credentials' => [
                'secret' => 's3_secret',
            ],
        ]);
    }
}
