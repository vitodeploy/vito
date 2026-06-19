<?php

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
        if (Schema::hasColumn('cron_jobs', 'name')) {
            return;
        }

        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->string('name')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('cron_jobs', 'name')) {
            return;
        }

        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->dropIndex('cron_jobs_name_index');
            $table->dropColumn('name');
        });
    }
};
