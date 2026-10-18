<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['workers', 'cron_jobs'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->foreignId('component_capability_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        Schema::table('components', function (Blueprint $table): void {
            $table->string('installed_runtime_version')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['workers', 'cron_jobs'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('component_capability_id');
            });
        }

        Schema::table('components', function (Blueprint $table): void {
            $table->dropColumn('installed_runtime_version');
        });
    }
};
