<?php

use App\Models\StorageProvider;
use App\StorageProviders\S3;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $wasabiProviders = StorageProvider::query()
            ->where('provider', 'wasabi')
            ->get();

        /** @var StorageProvider $provider */
        foreach ($wasabiProviders as $provider) {
            $provider->provider = S3::id();
            $credentials = $provider->credentials;
            $credentials['api_url'] = "https://{$credentials['bucket']}.s3.{$credentials['region']}.wasabisys.com";
            $provider->credentials = $credentials;
            $provider->save();
        }
    }

    public function down(): void
    {
        //
    }
};
