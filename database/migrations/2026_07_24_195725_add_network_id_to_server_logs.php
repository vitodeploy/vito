<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_logs', function (Blueprint $table): void {
            $table->foreignId('network_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
            $table->index('network_id');
        });
    }

    public function down(): void
    {
        Schema::table('server_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('network_id');
        });
    }
};
