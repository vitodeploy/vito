<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metrics', function (Blueprint $table): void {
            $table->unsignedInteger('cpu_cores')->nullable()->after('disk_free');
            $table->unsignedInteger('cpu_physical_cores')->nullable()->after('cpu_cores');
            $table->decimal('cpu_usage_percent', 5, 2)->nullable()->after('cpu_physical_cores');
            $table->json('cpu_per_core_usage_percent')->nullable()->after('cpu_usage_percent');
            $table->decimal('cpu_steal_percent', 5, 2)->nullable()->after('cpu_per_core_usage_percent');
            $table->decimal('swap_total', 15, 0)->nullable()->after('cpu_steal_percent');
            $table->decimal('swap_used', 15, 0)->nullable()->after('swap_total');
            $table->decimal('swap_free', 15, 0)->nullable()->after('swap_used');
            $table->decimal('swap_used_percent', 5, 2)->nullable()->after('swap_free');
            $table->unsignedInteger('oom_kill_count')->nullable()->after('swap_used_percent');
            $table->decimal('uptime_seconds', 15, 2)->nullable()->after('oom_kill_count');
            $table->boolean('reboot_required')->nullable()->after('uptime_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('metrics', function (Blueprint $table): void {
            $table->dropColumn([
                'cpu_cores',
                'cpu_physical_cores',
                'cpu_usage_percent',
                'cpu_per_core_usage_percent',
                'cpu_steal_percent',
                'swap_total',
                'swap_used',
                'swap_free',
                'swap_used_percent',
                'oom_kill_count',
                'uptime_seconds',
                'reboot_required',
            ]);
        });
    }
};
