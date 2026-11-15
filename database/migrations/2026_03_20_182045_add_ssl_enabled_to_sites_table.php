<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('ssl_enabled')->default(false)->after('force_ssl');
            $table->text('vhost_template')->nullable()->after('ssl_enabled');
            $table->boolean('vhost_generation_enabled')->default(true)->after('vhost_template');
        });

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

        DB::table('sites')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('services')
                    ->whereColumn('services.server_id', 'sites.server_id')
                    ->where('services.type', 'webserver')
                    ->where('services.name', 'caddy');
            })
            ->update(['ssl_enabled' => true, 'force_ssl' => true]);

        DB::table('sites')->update(['vhost_generation_enabled' => false]);
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['ssl_enabled', 'vhost_template', 'vhost_generation_enabled']);
        });
    }
};
