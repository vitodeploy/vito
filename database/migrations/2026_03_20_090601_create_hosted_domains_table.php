<?php

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use App\Models\Site;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hosted_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->string('type');
            $table->string('status')->default(HostedDomainStatus::CREATING->value);
            $table->string('ssl_method')->default(SslMethod::LETSENCRYPT->value);
            $table->foreignId('ssl_id')->nullable()->constrained('ssls')->nullOnDelete();
            $table->timestamps();

            $table->unique(['site_id', 'domain']);
        });

        // Seed existing sites' domains into hosted_domains
        Site::query()->each(function (Site $site): void {
            $site->hostedDomains()->create([
                'domain' => $site->domain,
                'type' => HostedDomainType::PRIMARY,
                'status' => HostedDomainStatus::ACTIVE,
                'ssl_method' => SslMethod::LETSENCRYPT,
            ]);

            foreach ($site->aliases ?? [] as $alias) {
                $site->hostedDomains()->create([
                    'domain' => $alias,
                    'type' => HostedDomainType::ALIAS,
                    'status' => HostedDomainStatus::ACTIVE,
                    'ssl_method' => SslMethod::LETSENCRYPT,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosted_domains');
    }
};
