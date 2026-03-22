<?php

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use App\Enums\SslType;
use App\Models\Site;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'domain']);
            $table->index('status');
            $table->index('domain');
        });

        // Seed existing sites' domains into hosted_domains
        Site::query()->each(function (Site $site): void {
            $isCaddy = DB::table('services')
                ->where('server_id', $site->server_id)
                ->where('type', 'webserver')
                ->where('name', 'caddy')
                ->exists();

            $activeSsl = $isCaddy ? null : $site->ssls()
                ->where('is_active', true)
                ->where('status', 'created')
                ->first();

            $sslMethod = $activeSsl?->type === SslType::CUSTOM->value
                ? SslMethod::CUSTOM
                : SslMethod::LETSENCRYPT;

            $sslDomains = $activeSsl?->domains ?? [];

            $domains = [
                ['domain' => $site->domain, 'type' => HostedDomainType::PRIMARY, 'covered' => true],
                ...array_map(fn (string $alias) => [
                    'domain' => $alias,
                    'type' => HostedDomainType::ALIAS,
                    'covered' => ! $activeSsl || in_array($alias, $sslDomains),
                ], $site->aliases ?? []),
            ];

            foreach ($domains as $entry) {
                $site->hostedDomains()->create([
                    'domain' => $entry['domain'],
                    'type' => $entry['type'],
                    'status' => HostedDomainStatus::ACTIVE,
                    'ssl_method' => $entry['covered'] ? $sslMethod : SslMethod::NONE,
                    'ssl_id' => $entry['covered'] ? $activeSsl?->id : null,
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
