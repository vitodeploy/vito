<?php

use App\Models\NotificationChannel;
use App\Models\User;
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
        } elseif (Schema::hasTable('plugins')) {
            $indexes = DB::select("SHOW INDEXES FROM plugins WHERE Key_name = 'plugins_is_enabled_priority_index'");
            if (! empty($indexes)) {
                DB::statement('DROP INDEX plugins_is_enabled_priority_index ON plugins');
            }
        }
        Schema::table('notification_channels', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->after('id')->nullable();
        });
        $owner = User::query()->where('is_admin', true)->orderBy('id')->first();
        if ($owner) {
            NotificationChannel::query()->update(['user_id' => $owner->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_channels', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
