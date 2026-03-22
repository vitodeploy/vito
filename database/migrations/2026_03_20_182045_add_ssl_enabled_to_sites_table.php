<?php

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
        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('ssl_enabled')->default(false)->after('force_ssl');
            $table->text('vhost_template')->nullable()->after('ssl_enabled');
            $table->boolean('vhost_generation_enabled')->default(true)->after('vhost_template');
        });

        // Backfill ssl_enabled for sites that already have an active SSL certificate
        DB::table('sites')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ssls')
                    ->whereColumn('ssls.site_id', 'sites.id')
                    ->where('ssls.expires_at', '>=', now())
                    ->where('ssls.status', 'created')
                    ->where('ssls.is_active', true);
            })
            ->update(['ssl_enabled' => true]);

        // Caddy manages TLS automatically, so enable ssl_enabled and force_ssl
        DB::table('sites')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('services')
                    ->whereColumn('services.server_id', 'sites.server_id')
                    ->where('services.type', 'webserver')
                    ->where('services.name', 'caddy');
            })
            ->update(['ssl_enabled' => true, 'force_ssl' => true]);

        // Disable vhost generation for existing sites to support legacy sites
        // that may have manually edited vhosts and need updating before enabling
        DB::table('sites')->update(['vhost_generation_enabled' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['ssl_enabled', 'vhost_template', 'vhost_generation_enabled']);
        });
    }
};
