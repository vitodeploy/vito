<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_providers', function (Blueprint $table) {
            $table->dropColumn('token');
            $table->dropColumn('refresh_token');
            $table->dropColumn('token_expires_at');
            $table->dropColumn('label');
            $table->dropColumn('connected');
            $table->unsignedBigInteger('user_id')->after('id');
            $table->string('profile')->after('user_id');
            $table->longText('credentials')->nullable()->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('storage_providers', function (Blueprint $table) {
            $table->string('token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('token_expires_at')->nullable();
            $table->string('label')->nullable();
            $table->dropColumn('user_id');
            $table->dropColumn('profile');
            $table->dropColumn('credentials');
        });
    }
};
