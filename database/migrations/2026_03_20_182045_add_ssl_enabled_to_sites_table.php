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
