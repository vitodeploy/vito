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
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS plugins_is_enabled_priority_index');
        }

        if (DB::getDriverName() === 'mysql') {
            $indexes = DB::select("SHOW INDEXES FROM plugins WHERE Key_name = 'plugins_is_enabled_priority_index'");
            if (! empty($indexes)) {
                DB::statement('DROP INDEX plugins_is_enabled_priority_index ON plugins');
            }
        }

        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->unsignedInteger('site_id')->nullable()->after('server_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->dropColumn('site_id');
        });
    }
};
