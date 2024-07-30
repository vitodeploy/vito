<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_providers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->after('id');
            $table->string('profile')->after('user_id');
            $table->longText('credentials')->nullable()->after('provider');
        });
        Schema::table('storage_providers', function (Blueprint $table) {
            $table->dropColumn(['token', 'refresh_token', 'token_expires_at', 'label', 'connected']);
        });
    }

    public function down(): void
    {
        Schema::table('storage_providers', function (Blueprint $table) {
            $table->string('token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('token_expires_at')->nullable();
            $table->string('label')->nullable();
        });
        Schema::table('storage_providers', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'profile', 'credentials']);
        });
    }
};
