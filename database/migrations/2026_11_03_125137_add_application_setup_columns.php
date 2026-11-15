<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table): void {
            $table->boolean('deploy_on_ready')->default(false);
            $table->boolean('auto_deploy_failed')->default(false);
        });
        Schema::table('environment_domains', function (Blueprint $table): void {
            $table->foreignId('dns_domain_id')->nullable()->constrained('domains')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('environment_domains', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dns_domain_id');
        });
        Schema::table('environments', function (Blueprint $table): void {
            $table->dropColumn(['deploy_on_ready', 'auto_deploy_failed']);
        });
    }
};
