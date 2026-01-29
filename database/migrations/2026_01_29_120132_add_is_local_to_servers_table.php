<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->boolean('is_local')->default(false);
            $table->json('local_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->dropColumn('is_local');
            $table->dropColumn('local_data');
        });
    }
};
