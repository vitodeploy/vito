<?php

use App\Enums\StorageProvider;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $wasabiProviders = \App\Models\StorageProvider::query()
            ->where('provider', 'wasabi')
            ->get();

        /** @var \App\Models\StorageProvider $provider */
        foreach ($wasabiProviders as $provider) {
            $provider->provider = StorageProvider::S3;
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
