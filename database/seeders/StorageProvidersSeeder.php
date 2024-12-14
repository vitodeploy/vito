<?php

namespace Database\Seeders;

use App\Models\StorageProvider;
use Illuminate\Database\Seeder;

class StorageProvidersSeeder extends Seeder
{
    public function run(): void
    {
        StorageProvider::factory()->create([
            'profile' => 'FTP',
            'provider' => \App\Enums\StorageProvider::FTP,
            'credentials' => [
                'host' => 'ftp.example.com',
                'username' => 'ftp_user',
                'password' => 'ftp_password',
            ],
        ]);

        StorageProvider::factory()->create([
            'profile' => 'S3',
            'provider' => \App\Enums\StorageProvider::S3,
            'credentials' => [
                'secret' => 's3_secret',
            ],
        ]);
    }
}
