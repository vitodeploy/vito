<?php

namespace Database\Factories;

use App\Models\StorageProvider;
use App\Models\User;
use App\StorageProviders\Dropbox;
use App\StorageProviders\FTP;
use App\StorageProviders\S3;
use App\StorageProviders\SFTP;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorageProvider>
 */
class StorageProviderFactory extends Factory
{
    public function definition(): array
    {
        $provider = $this->faker->randomElement(array_keys(config('storage-provider.providers')));

        return [
            'profile' => $this->faker->word(),
            'provider' => $provider,
            'credentials' => $this->credentialsFor($provider),
            'user_id' => User::factory(),
        ];
    }

    public function dropbox(): static
    {
        return $this->state(fn (): array => [
            'provider' => Dropbox::id(),
            'credentials' => $this->credentialsFor(Dropbox::id()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function credentialsFor(string $provider): array
    {
        return match ($provider) {
            Dropbox::id() => [
                'app_key' => 'test-app-key',
                'app_secret' => 'test-app-secret',
                'refresh_token' => 'test-refresh-token',
            ],
            S3::id() => [
                'api_url' => 'https://s3.amazonaws.com',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'region' => 'us-east-1',
                'bucket' => 'test-bucket',
                'path' => '/backups',
            ],
            FTP::id() => [
                'host' => '1.2.3.4',
                'port' => 21,
                'path' => '/home/vito',
                'username' => 'username',
                'password' => 'password',
                'ssl' => false,
                'passive' => true,
            ],
            SFTP::id() => [
                'host' => '1.2.3.4',
                'port' => 22,
                'path' => '/home/vito',
                'username' => 'username',
                'password' => 'password',
            ],
            default => [
                'path' => '/home/vito/backups',
            ],
        };
    }
}
