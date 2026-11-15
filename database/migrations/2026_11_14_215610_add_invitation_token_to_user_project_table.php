<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_project', function (Blueprint $table): void {
            $table->string('invitation_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('user_project', function (Blueprint $table): void {
            $table->dropUnique(['invitation_token']);
            $table->dropColumn('invitation_token');
        });
    }
};
