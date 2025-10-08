<?php

use App\Enums\UserRole;
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
        }

        $admins = User::query()->where('role', UserRole::ADMIN)->get('id');
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->dropColumn('role');
        });
        User::query()->whereIn('id', $admins)->update(['is_admin' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $admins = User::query()->where('is_admin', true)->get('id');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
            $table->string('role')->default(UserRole::USER)->after('password');
        });
        User::query()->whereIn('id', $admins)->update(['role' => UserRole::ADMIN]);
    }
};
